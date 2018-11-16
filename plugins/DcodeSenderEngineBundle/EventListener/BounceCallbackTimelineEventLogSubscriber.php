<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class BounceCallbackTimelineEventLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface|Translator
     */
    private $translator;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

     /**
     * @var integrationHelper
     */
    private $integrationHelper;

    /**
     * TimelineEventLogSubscriber constructor.
     *
     * @param TranslatorInterface    $translator
     * @param ModelFactory           $modelFactory
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        LeadEventLogRepository $LeadEventLogRepository,
        IntegrationHelper $integrationHelper
    ) {
        $this->translator             = $translator;
        $this->leadEventLogRepository = $LeadEventLogRepository;
        $this->integrationHelper      = $integrationHelper;      
    } 

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {        
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }
        
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('bounce_callback', $supportedFeatures)) {
            return;
        }

        $this->addEvents($event, 'dcodesenderengine.bouncecallback.email_bounced', 'mautic.plugin.bounce_callback.timeline.bounce');        
//        $this->addEvents($event, 'dcodesenderengine.bouncecallback.lead_dnc', 'mautic.plugin.bounce_callback.timeline.lead.dnc');
//        $this->addEvents($event, 'dcodesenderengine.bouncecallback.lead_unsubscribed', 'mautic.plugin.bounce_callback.timeline.lead.unsubscribed');        
    }

    /**
     * @param LeadTimelineEvent $event
     * @param                   $eventType
     * @param                   $eventTypeName
     */
    private function addEvents(LeadTimelineEvent $event, $eventType, $eventTypeName)
    {
        $eventTypeName = $this->translator->trans($eventTypeName);
        $event->addEventType($eventType, $eventTypeName);
     
        if (!$event->isApplicable($eventType)) {
            return;
        }

        $action = str_replace('dcodesenderengine.bouncecallback.', '', $eventType);
        $events = $this->leadEventLogRepository->getEventsByAction($action, $event->getLead(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventType, $events);

        if ($event->isEngagementCount()) {
            return;
        }

        // Add the logs to the event array
        foreach ($events['results'] as $log) {
            $event->addEvent(
                $this->getEventEntry($log, $eventType, $eventTypeName)
            );
        }
    }

    /**
     * @param array $log
     * @param       $eventType
     * @param       $eventTypeName
     *
     * @return array
     */
    private function getEventEntry(array $log, $eventType, $eventTypeName)
    {
        return [
            'event'           => $eventType,
            'eventId'         => $eventType.$log['id'],
            'eventType'       => $eventTypeName,
            'eventLabel'      => $this->getLabel($log, $eventType),
            'timestamp'       => $log['date_added'],
            'icon'            => $this->getIcon($log, $eventType),
            'contactId'       => $log['lead_id'],
        ];
    }

    /**
     * @param array $log
     * @param       $eventType
     *
     * @return string
     */
    private function getLabel(array $log, $eventType)
    {
        switch ($eventType){
            case "dcodesenderengine.bouncecallback.email_bounced":
                $properties = json_decode($log['properties'], true);

                if (!empty($properties['bounce_points']) && !empty($properties['status'])) {
                    if (!empty($properties['error_message'])){
                        return $this->translator->trans(
                            'mautic.plugin.bounce_callback.timeline.email_bounced_reason',
                            [
                                '%error_message%' => $properties['error_message'],
                                '%status%' => $properties['status'],
                                '%bounce_points%' => $properties['bounce_points'],
                            ]
                        );
                    }else{
                        return $this->translator->trans(
                            'mautic.plugin.bounce_callback.timeline.email_bounced',
                            [
                                '%status%' => $properties['status'],
                                '%bounce_points%' => $properties['bounce_points'],
                            ]
                        );
                    }            
                }else{
                    return $this->translator->trans('mautic.plugin.bounce_callback.timeline.bounce');
                }
            break;

            case "dcodesenderengine.bouncecallback.lead_dnc":
                $properties = json_decode($log['properties'], true);
                if (!empty($properties['bounce_points']) && !empty($properties['threshold'])) {
                    return $this->translator->trans(
                            'mautic.plugin.bounce_callback.timeline.lead.dnc_reason',
                            [
                                '%threshold%' => $properties['threshold'],
                                '%bounce_points%' => $properties['bounce_points'],
                            ]
                        );    
                }else{
                    return $this->translator->trans('mautic.plugin.bounce_callback.timeline.lead.dnc');   
                }
            break;
            case "dcodesenderengine.bouncecallback.lead_unsubscribed":
                $properties = json_decode($log['properties'], true);
                if (!empty($properties['bounce_points']) && !empty($properties['threshold'])) {
                    return $this->translator->trans(
                            'mautic.plugin.bounce_callback.timeline.lead.unsubscribed_reason',
                            [
                                '%threshold%' => $properties['threshold'],
                                '%bounce_points%' => $properties['bounce_points'],
                            ]
                        );    
                }else{
                    return $this->translator->trans('mautic.plugin.bounce_callback.timeline.lead.unsubscribed');   
                }
            break;
        }

        
    }

    /**
     * @param array $log
     * @param       $eventType
     *
     * @return string
     */
    private function getIcon(array $log, $eventType)
    {
        $properties = json_decode($log['properties'], true);

        switch ($properties['status']){
            case 3:
            case "3":
                return 'fa-info-circle';
            break;

            case 4:
            case "4":
                return 'fa-exclamation-triangle';
            break;

            case 5:
            case "5":
                return 'fa-exclamation-triangle';
            break;        
        }        

        if (!empty($properties['bounce_points']) && !empty($properties['threshold']) && $properties['bounce_points']>$properties['threshold']) {
            return "fa-times-circle";
        }

    }
}
