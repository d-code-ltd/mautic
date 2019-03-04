<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeMauticUtilsBundle\EventListener; 

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Event\LeadImportLeadIdentifyEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Class LeadTrackerIdentifiedSubsciber.
 */
class LeadImportIdentifySubsciber extends CommonSubscriber
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
            LeadEvents::LEAD_IMPORT_LEAD_IDENTIFY => ['leadImportTag', 0],
        ];
    }

    /**
     * 
     */
    public function leadImportTag(LeadImportLeadIdentifyEvent $event)
    {        
        /** @var \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\AbstractEnhancerIntegration $integration */
        $integration = $this->integrationHelper->getIntegrationObject('ImportEnhancer');
        if (!$integration){
            return;
        }
        
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        $features        = $integrationSettings->getSupportedFeatures();
        $featureSettings = $integrationSettings->getFeatureSettings();
            
        $lead = $event->getLead();
        $fieldData = $event->getFieldData();
        $fields = $event->getFields();
        $data = $event->getData();
        
        if (!empty($fieldData['_tags'])){
            if (mb_stristr($fieldData['_tags'], "|") !== FALSE){
                $this->leadModel->modifyTags($lead, explode("|", $fieldData['_tags']), null, true);
            }else{
                $this->leadModel->modifyTags($lead, $fieldData['_tags'], null, true);
            }
        }       
    }
}
