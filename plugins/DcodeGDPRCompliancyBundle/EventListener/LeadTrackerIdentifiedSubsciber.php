<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\EventListener; 

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Class LeadTrackerIdentifiedSubsciber.
 */
class LeadTrackerIdentifiedSubsciber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var IntegrationHelper
     */
    protected $logger;

    /**
     * @var LeadModel
     */
    protected $leadModel;
  
    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper       $integrationHelper
     * @param LoggerInterface          $logger
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        LoggerInterface $logger,
        LeadModel $leadModel         
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;
        $this->leadModel = $leadModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_TRACKER_IDENTIFIED => ['allowDNCLeadTrack', 0],
        ];
    }

    /**
     * 
     */
    public function allowDNCLeadTrack(LeadEvent $event)
    {        
        /** @var \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\AbstractEnhancerIntegration $integration */
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        $features        = $integrationSettings->getSupportedFeatures();
        $featureSettings = $integrationSettings->getFeatureSettings();
                
        if (in_array('do_not_track_unsubscribed', $features)){
            $lead = $event->getLead();
            $doNotContactList = $lead->getDoNotContact();

            var_dump($lead->getId(), count($doNotContactList));

            foreach ($doNotContactList as $doNotContact){
                var_dump($doNotContact->getReason(), $lead->getEmail());
                switch ($doNotContact->getReason()) {
                    case DoNotContact::BOUNCED:                       
                    case DoNotContact::MANUAL:                        
                    case DoNotContact::UNSUBSCRIBED:
                        if (!$lead->getEmail()){
                            //$event->setLead(null);
                            var_dump("Itt kéne most törölni a lead-et");
                        }
                    break;
                    default:
                        
                    break;
                }
            }
        }        
    }
}
