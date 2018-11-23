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
                if (!empty($featureSettings[$settingKey])){
                    $action = $featureSettings[$settingKey];
                }else{
                    $action = $integration::$defaultGDPRFieldBehaviour;
                }

                switch ($action){
                    case "hash":                        
                        $value = $lead->__get($fieldAlias);
                        if (trim($value)){
                            if (in_array($fieldAlias, $integration::$separateHashFields)){
                                //acquire hashable value and remove it                                
                                $lead->addUpdatedField($fieldAlias,null,$value);

                                //hash the value and store it in special field                                
                                $lead->addUpdatedField($fieldAlias.'_hash', $integration->hashValue($lead->getId(), trim($value),$featureSettings['hash_salt']), $value);
                            }else{
                                //hash the value in the field
                                $lead->addUpdatedField($fieldAlias.'_hash', $integration->hashValue($lead->getId(), trim($value),$featureSettings['hash_salt']), $value);
                            }    
                        }
                    break;                
                    case "remove":
                        $lead->addUpdatedField($fieldAlias, null, $value);
                    break;                
                }
                $this->logger->log("GDPR: lead{$lead->getId()}->{$fieldAlias} {$action}ed");
            }
            

            $manipulationLog = new LeadEventLog();
            $manipulationLog->setLead($lead);
            $manipulationLog->setAction('gdpr_clean');
    
            $lead->addEventLog($manipulationLog);

            $this->leadModel->saveEntity($lead);
        }           
    }
}
