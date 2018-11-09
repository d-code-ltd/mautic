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

class BounceCallbackEmailSubscriber implements EventSubscriberInterface
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
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('bounce_callback', $supportedFeatures)) {
            return;
        }

        //$integrationSettings = $integration->getIntegrationSettings();        
        //$featureSettings     = $integrationSettings->getFeatureSettings();  


        $helper = $event->getHelper();
        $lead = $event->getLead();

        $event->addTextHeader('X-M-Lead', $lead['id']);            
        $event->addTextHeader('X-M-email', $event->getEmail()->getId());
        $event->addTextHeader('X-M-idHash', $event->getIdHash());
        
        $callbackUrl   = $this->router->generate('mautic_notification_popup', ['idHash' => $event->getIdHash()], UrlGeneratorInterface::ABSOLUTE_URL);


        $event->addTextHeader('X-mailengine-bounce-callback', $callbackUrl);

        $this->logger->addDebug("EMAIL: DcodeSenderEngine plugin added X-mailengine-bounce-callback to {$returnPath}");
        
    }
}
