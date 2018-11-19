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
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * @var Router
     */
    protected $router;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper       $integrationHelper
     * @param LoggerInterface          $logger
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        LoggerInterface $logger,
        Router $router
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;
        $this->router = $router;        
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

        if (!empty($lead['id'])){
            $event->addTextHeader('X-SenderEngine-Lead', $lead['id']);            
        }
        if (!empty($event->getEmail())){
            $event->addTextHeader('X-SenderEngine-email', $event->getEmail()->getId());
        }
        $event->addTextHeader('X-SenderEngine-idHash', $event->getIdHash());
        
        $callbackUrl   = $this->router->generate('mauticplugin.dcodesenderengin.route.bouncecallback', ['idHash' => $event->getIdHash()], UrlGeneratorInterface::ABSOLUTE_URL);


        $event->addTextHeader('X-SenderEngine-bounce-callback', $callbackUrl);

        $this->logger->addDebug("EMAIL: DcodeSenderEngine plugin added X-mailengine-bounce-callback to {$callbackUrl}");        
    }
}
