<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;

class ReplaceReturnPathSubscriber implements EventSubscriberInterface
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
            EmailEvents::EMAIL_ON_SEND          => ['onEmailSend', 0],
        ];
    }
  
    /**
     * Change Return Path
     *
     * @param EmailSendEvent $event
     */
    public function onEmailSend(EmailSendEvent $event)
    {
        var_dump('1');
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        var_dump('2');
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('replace_return_path', $supportedFeatures)) {
            return;
        }

        $integrationSettings = $integration->getIntegrationSettings();
        $features            = $integration->getSupportedFeatures();
        $featureSettings     = $integrationSettings->getFeatureSettings();  

        var_dump('3');

        $helper = $event->getHelper();
        if ($helper && !empty($featureSettings['return_path_format'])) {
            $lead = $event->getLead();
            $returnPath = str_ireplace(array(
                '{idHash}',
                '{leadId}',
                '{emailId}'
            ),array(
                $event->getIdHash(),
                lead['id'],
                $event->getEmail()->getId()
            ),$featureSettings['return_path_format']);

            var_dump($returnPath);

            $event->addTextHeader('Return-path', $returnPath);            

            $this->logger->addDebug("EMAIL: DcodeSenderEngine plugin changed return-path to {$returnPath}");
        }
    }
}
