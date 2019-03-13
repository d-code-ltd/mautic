<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;



use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;

/**
 * Class WebhookSubscriber.
 */
class GDPRCompliancyChannelSubscriptionChangeSubscriber extends CommonSubscriber
{
    /**
     * @var TranslatorInterface|Translator
     */
    public $translator;

    /**
     * @var LeadEventLogRepository
     */
    protected $leadEventLogRepository;

     /**
     * @var integrationHelper
     */
    protected $integrationHelper;

    protected $fieldModel;

    /**
     * TimelineEventLogSubscriber constructor.
     *
     * @param TranslatorInterface    $translator
     * @param ModelFactory           $modelFactory
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger,
        LeadEventLogRepository $LeadEventLogRepository,
        IntegrationHelper $integrationHelper,
        FieldModel $fieldModel,
        LeadModel $leadModel
    ) {
        $this->translator             = $translator;
        $this->logger             = $logger;
        $this->leadEventLogRepository = $LeadEventLogRepository;
        $this->integrationHelper      = $integrationHelper;
        $this->fieldModel             = $fieldModel;
        $this->leadModel             = $leadModel;
    }     


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [            
            LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED => ['onChannelSubscriptionChange', 0],
        ];
    }

    /**
     * @param ChannelSubscriptionChange $event
     */
    public function onChannelSubscriptionChange(ChannelSubscriptionChange $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        if (!$integration){
            return;
        }
        
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        $supportedFeatures = $integration->getSupportedFeatures();        
        $featureSettings     = $integrationSettings->getFeatureSettings();  

        if (empty($featureSettings['hash_salt'])){
            $this->logger->addError('GDPR: hash_salt setting is required for proper operation');
            return;
        }

        $newStatus = $event->getNewStatus();
        $oldStatus = $event->getOldStatus();
 

        if ($oldStatus == DoNotContact::IS_CONTACTABLE  && in_array($newStatus,[DoNotContact::BOUNCED, DoNotContact::UNSUBSCRIBED, DoNotContact::MANUAL])){
            $leadFields = $this->fieldModel->getLeadFields();
            
            $lead = $event->getLead();
            $channel = $event->getChannel();
            
            foreach ($leadFields as $leadFieldEntity){
                $fieldAlias = $leadFieldEntity->getAlias();
                $settingKey = $integration->getFieldSettingKey($fieldAlias);

                if (mb_ereg('_hash$', $fieldAlias) AND in_array(mb_ereg_replace('_hash$','',$fieldAlias), $integration::$separateHashFields)){
                    $this->logger->warning("GDPR: {$fieldAlias} is a separateHashFields");
                    continue;
                }


                if (!empty($featureSettings[$settingKey])){
                    $action = $featureSettings[$settingKey];
                    $this->logger->warning("GDPR: $settingKey has setting: {$featureSettings[$settingKey]}");
                }else{
                    $action = $integration::$defaultGDPRFieldBehaviour;
                    $this->logger->warning("GDPR: $settingKey defaults to: {$integration::$defaultGDPRFieldBehaviour}");
                }

                switch ($action){
                    case "hash":                        
                        $value = $lead->__get($fieldAlias);
                        if (trim($value)){                            
                            if (in_array($fieldAlias, $integration::$separateHashFields)){
                                $this->logger->warning("GDPR: {$fieldAlias} member of separateHashFields");
                                //acquire hashable value and remove it                                
                                $lead->addUpdatedField($fieldAlias,null,$value);

                                //hash the value and store it in special field                                
                                $lead->addUpdatedField($fieldAlias.'_hash', $integration->hashValue(trim($value),$featureSettings['hash_salt']), $value);
                                $this->logger->warning("GDPR: {$value} hashed as ".$integration->hashValue(trim($value),$featureSettings['hash_salt'])." into {$fieldAlias}_hash");
                            }else{
                                //hash the value in the field
                                $lead->addUpdatedField($fieldAlias, $integration->hashValue(trim($value),$featureSettings['hash_salt']), $value);
                            }    
                        }
                    break;                
                    case "remove":
                        $value = $lead->__get($fieldAlias);
                        $lead->addUpdatedField($fieldAlias, null, $value);
                    break;                
                }
                $this->logger->warning("GDPR: lead{$lead->getId()}->{$fieldAlias} {$action}ed");
            }
            

            $manipulationLog = new LeadEventLog();
            $manipulationLog->setLead($lead);
            $manipulationLog->setAction('gdpr_clean');
    
            $lead->addEventLog($manipulationLog);

            $this->leadModel->saveEntity($lead);
        }           
    }
}