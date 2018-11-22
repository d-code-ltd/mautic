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

        $supportedFeatures = $integration->getSupportedFeatures();        
        $featureSettings     = $integrationSettings->getFeatureSettings();  

        var_dump($featureSettings);

        $leadFields = $this->fieldModel->getLeadFields();
        var_dump($leadFields);


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
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias)));
                    
                break;                
                case "remove":
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $fieldAlias)));
                    $lead->$method(null);
                break;                
            }    
            
        }



        


        

        

        if ($newStatus == DoNotContact::BOUNCED){
            //ide jön majd minden
            var_dump();
        }   


/*
    public function checkForDuplicateContact(array $queryFields, Lead $lead = null, $returnWithQueryFields = false, $onlyPubliclyUpdateable = false)
    {
        // Search for lead by request and/or update lead fields if some data were sent in the URL query
        if (empty($this->availableLeadFields)) {
            $filter = ['isPublished' => true, 'object' => 'lead'];

            if ($onlyPubliclyUpdateable) {
                $filter['isPubliclyUpdatable'] = true;
            }

            $this->availableLeadFields = $this->leadFieldModel->getFieldList(
                false,
                false,
                $filter
            );
        }

        if (is_null($lead)) {
            $lead = new Lead();
        }

        $uniqueFields    = $this->leadFieldModel->getUniqueIdentifierFields();
        $uniqueFieldData = [];
        $inQuery         = array_intersect_key($queryFields, $this->availableLeadFields);
        $values          = $onlyPubliclyUpdateable ? $inQuery : $queryFields;

        // Run values through setFieldValues to clean them first
        $this->setFieldValues($lead, $values, false, false);
        $cleanFields = $lead->getFields();

        foreach ($inQuery as $k => $v) {
            if (empty($queryFields[$k])) {
                unset($inQuery[$k]);
            }
        }

        foreach ($cleanFields as $group) {
            foreach ($group as $key => $field) {
                if (array_key_exists($key, $uniqueFields) && !empty($field['value'])) {
                    $uniqueFieldData[$key] = $field['value'];
                }
            }
        }

        // Check for leads using unique identifier
        if (count($uniqueFieldData)) {
            $existingLeads = $this->getRepository()->getLeadsByUniqueFields($uniqueFieldData, ($lead) ? $lead->getId() : null);

            if (!empty($existingLeads)) {
                $this->logger->addDebug("LEAD: Existing contact ID# {$existingLeads[0]->getId()} found through query identifiers.");
                // Merge with existing lead or use the one found
                $lead = ($lead->getId()) ? $this->mergeLeads($lead, $existingLeads[0]) : $existingLeads[0];
            }
        }

        return $returnWithQueryFields ? [$lead, $inQuery] : $lead;
    }
 */





         
    }
}
