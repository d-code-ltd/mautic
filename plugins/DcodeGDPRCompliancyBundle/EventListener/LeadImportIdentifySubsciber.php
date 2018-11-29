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
            LeadEvents::LEAD_IMPORT_LEAD_IDENTIFY => ['leadImportIdentify', 0],
        ];
    }

    /**
     * 
     */
    public function leadImportIdentify(LeadImportLeadIdentifyEvent $event)
    {        
        /** @var \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\AbstractEnhancerIntegration $integration */
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        $features        = $integrationSettings->getSupportedFeatures();
        $featureSettings = $integrationSettings->getFeatureSettings();
                
        if (in_array('do_not_resubscribe_unsubscibed_import', $features)){
            $lead = $event->getLead();
            $fieldData = $event->getFieldData();

            if (empty($lead) || !$lead->getId()){
                if (!empty($fieldData['email'])){
                    $filter     = ['string' => '', 'force' => []];
                    $filter['force'][] = [
                        'column' => 'l.email_hash',
                        'expr'   => 'eq',
                        'value'  => $integration->hashValue($fieldData['email'], $featureSettings['hash_salt']),
                    ];
            
                    $result = $this->leadModel->getEntities([
                        'filter' => $filter,
                        'limit'          => 1,
                        'hydration_mode' => 'HYDRATE_ARRAY'
                    ]);
                    var_dump($result);
                }

/*
                $filter     = ['string' => $search, 'force' => []];
                //vagy
                $fields = $this->getModel('lead.field')->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => 'f.isPublished',
                                    'expr'   => 'eq',
                                    'value'  => true,
                                ],
                                [
                                    'column' => 'f.isShortVisible',
                                    'expr'   => 'eq',
                                    'value'  => true,
                                ],
                                [
                                    'column' => 'f.object',
                                    'expr'   => 'like',
                                    'value'  => 'lead',
                                ],
                            ],
                        ],
                        'hydration_mode' => 'HYDRATE_ARRAY',
                    ]
                );


                $model->getEntities([
                    'start'          => $start,
                    'limit'          => $limit,
                    'filter'         => $filter,
                    'orderBy'        => $orderBy,
                    'orderByDir'     => $orderByDir,
                    'withTotalCount' => true,
                ]);
*/
            }else{
                //lead is already identified by unique identifier. Nothing to do here                
            }
        }        
    }
}
