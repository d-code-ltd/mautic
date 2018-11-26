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
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper       $integrationHelper
     * @param LoggerInterface          $logger
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        LoggerInterface $logger        
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;        
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

            
            $integration->buildHashFields();
            $this->logger->addDebug("GDPR: GDPRCompliancy plugin fields added");



            $integrationRepository = $this->em->getRepository('PluginBundle:Integration');
            var_dump($integration->getId());
            $oldIntegrationSettings = $integrationRepository->find($integration->getId());

            var_dump($integrationSettings->getFeatureSettings());
            var_dump($oldIntegrationSettings->getFeatureSettings());
                
            
        }

        

    }
}
