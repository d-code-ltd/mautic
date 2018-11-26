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

/**
 * Class PluginSubscriber.
 */
class PluginSubscriber extends CommonSubscriber
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
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['buildEnhancerFields', 0],
        ];
    }

    /**
     * @param PluginIntegrationEvent $event
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildEnhancerFields(PluginIntegrationEvent $event)
    {        
        /** @var \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\AbstractEnhancerIntegration $integration */
        $integration = $event->getIntegration();
        if ($integration->getName() == 'GDPRCompliancy') {
            $integrationSettings = $integration->getIntegrationSettings();
            if (!$integration || $integrationSettings->getIsPublished() === false) {
                return;
            }

            $featureSettings = $integrationSettings->getFeatureSettings();

            $integration->buildHashFields();
            $this->logger->addDebug("GDPR: GDPRCompliancy plugin fields added");


            //var_dump($oldIntegrationSettings->getFeatureSettings());
            $changes = $integrationSettings->getChanges();
            
            if (!empty($changes['featureSettings']) && !empty($changes['featureSettings'][0]) && !empty($changes['featureSettings'][1])){
                $oldValues = $changes['featureSettings'][0];
                $newValues = $changes['featureSettings'][1];

                $fieldsToHash = [];
                $fieldsToRemove = [];

                foreach ($newValues as $settingKey => $settingValue){
                    if (!mb_ereg('-gdpr_behaviour$',$settingKey)){
                        continue;
                    }

                    if ($newValues[$settingKey] == 'hash' && $oldValues[$settingKey] != 'hash' ){
                        $fieldsToHash[] = str_replace('-gdpr_behaviour', '', $settingKey);
                        $this->logger->addDebug("GDPR: {$settingKey} set to {$newValues[$settingKey]} from {$oldValues[$settingKey]} => {$newValues[$settingKey]}ing {$settingKey} field of all DNC users");
                    }

                    if ($newValues[$settingKey] == 'remove' && $oldValues[$settingKey] != 'remove' ){
                        $fieldsToRemove[] = str_replace('-gdpr_behaviour', '', $settingKey);
                        $this->logger->addDebug("GDPR: {$settingKey} set to {$newValues[$settingKey]} from {$oldValues[$settingKey]} => {$newValues[$settingKey]}ing {$settingKey} field of all DNC users");
                    }
                }

                if (!empty($fieldsToHash) || !empty($fieldsToRemove)){
                    set_time_limit(0);
                    $dncRepository = $this->em->getRepository('MauticLeadBundle:DoNotContact');
                    $dncList = $dncRepository->getChannelList('email');
                
                    if (!empty($dncList)){
                        $leadRepository = $this->em->getRepository('MauticLeadBundle:Lead');
                        $dncLeads = $leadRepository->findBy( array('id' => array_keys($dncList)), array('id' => 'DESC') );

                        unset($dncList);

                        if (!empty($dncLeads)){
                            foreach ($dncLeads as $lead){
                                $lead  = $this->leadModel->getEntity($lead->getId());
                                foreach ($fieldsToHash as $fieldAlias){
                                    $value = $lead->__get($fieldAlias);
                                    if (trim($value)){
                                        if (in_array($fieldAlias, $integration::$separateHashFields)){
                                            $this->logger->warning("GDPR: {$fieldAlias} member of separateHashFields");
                                            
                                            //acquire hashable value and remove it                                
                                            $lead->addUpdatedField($fieldAlias,null,$value);

                                            //hash the value and store it in special field                                
                                            $lead->addUpdatedField($fieldAlias.'_hash', $integration->hashValue($lead->getId(), trim($value),$featureSettings['hash_salt']), $value);
                                        }else{
                                            //hash the value in the field
                                            $lead->addUpdatedField($fieldAlias, $integration->hashValue($lead->getId(), trim($value),$featureSettings['hash_salt']), $value);
                                        }    
                                    }
                                }                                
                                foreach ($fieldsToRemove as $fieldAlias){
                                    $value = $lead->__get($fieldAlias);
                                    if (trim($value)){
                                        $lead->addUpdatedField($fieldAlias,null,$value);    
                                    }                                    
                                }

                                $this->leadModel->saveEntity($lead);
                            }
                        }      
                    }
                }
            }


        }

        

    }
}
