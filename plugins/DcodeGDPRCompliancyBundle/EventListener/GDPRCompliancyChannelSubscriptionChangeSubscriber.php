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
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Model\FieldModel;

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
        LeadEventLogRepository $LeadEventLogRepository,
        IntegrationHelper $integrationHelper,
        FieldModel $fieldModel
    ) {
        $this->translator             = $translator;
        $this->leadEventLogRepository = $LeadEventLogRepository;
        $this->integrationHelper      = $integrationHelper;
        $this->fieldModel             = $fieldModel;
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

        $newStatus = $event->getNewStatus();

 

        if ($newStatus == DoNotContact::BOUNCED){
            $supportedFeatures = $integration->getSupportedFeatures();        
            $featureSettings     = $integrationSettings->getFeatureSettings();  

            $leadFields = $this->fieldModel->getLeadFields();
            

            $lead = $event->getLead();
            $channel = $event->getChannel();
            
            foreach ($availableFields as $leadFieldEntity){
                $fieldAlias = $leadFieldEntity->getAlias();
                $settingKey = $integration->getFieldSettingKey($fieldAlias);
                if (!empty($featureSettings[$settingKey])){
                    $action = $featureSettings[$settingKey];
                }else{
                    $action = $integration::$defaultGDPRFieldBehaviour;
                }

                switch ($action){
                    case "hash":
                        $getMethod = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias)));    
                        $value = $lead->$getMethod();
                        if (trim($value)){
                            $setMethod = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias)));
                            if (in_array($fieldAlias, $integration::$separateHashFields)){
                                //acquire hashable value and remove it
                                $value = $lead->$getMethod();
                                $lead->$setMethod(null);

                                //hash the value and store it in special field
                                $setMethod = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias.'_hash'))); 
                                $lead->$setMethod($integration->hashValue(trim($value)));
                            }else{
                                //hash the value in the field
                                $lead->$setMethod($integration->hashValue(trim($value)));
                            }    
                        }
                    break;                
                    case "remove":
                        $setMethod = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias)));
                        $lead->$method(null);
                    break;                
                }                
            }
            $this->fieldModel->saveEntity($lead);
        }           
    }
}
