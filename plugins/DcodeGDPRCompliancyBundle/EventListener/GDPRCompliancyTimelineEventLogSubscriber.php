<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class GDPRCompliancyTimelineEventLogSubscriber implements EventSubscriberInterface
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
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        if (!$integration){
            return;
        }
        
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }
                        
        $this->addEvents($event, 'dcode.gdprcompiancy.gdpr_clean', 'mautic.plugin.gdprcompliancy.timeline.lead.gdpr_clean');        
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
     
        /*
        $citrixEvents = $this->model->getRepository()->getEventsForTimeline(
                    [$product, $type],
                    $event->getLeadId(),
                    $event->getQueryOptions()
                );
         */

        if (!$event->isApplicable($eventType)) {
            return;
        }

        $action = str_replace('dcode.gdprcompiancy.', '', $eventType);
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
            case "dcode.gdprcompiancy.gdpr_clean":
                return $this->translator->trans('mautic.plugin.bounce_callback.timeline.lead.gdpr_clean.label');   
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
        switch ($eventType){           
            case "ddcode.gdprcompiancy.gdpr_clean":
                return "fa-user-times";
            break;  
        }
    }
}
