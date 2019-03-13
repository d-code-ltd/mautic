<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeMauticUtilsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * PageSubscriber constructor.
     *
     * @param AssetsHelper      $assetsHelper
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(AssetsHelper $assetsHelper, IntegrationHelper $integrationHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->assetsHelper      = $assetsHelper;
        $this->integrationHelper = $integrationHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],            
        ];
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('WhiteLabel');
        $settings          = $integration->getIntegrationSettings();
        $features          = $settings->getSupportedFeatures();
    
        if (!$integration || !$settings || $settings->getIsPublished() === false) {
            return;
        }
    
        


        $this->assetsHelper->addCustomDeclaration('<link rel="icon" type="image/x-icon" href="'.$this->coreParametersHelper->getParameter('whitelabel_branding_favicon', 'media/images/favicon.ico').'" />
                                                   <link rel="icon" sizes="192x192" href="'.$this->coreParametersHelper->getParameter('whitelabel_branding_favicon', 'media/images/favicon.ico').'" />');
        $this->assetsHelper->addCustomDeclaration('<link rel="apple-touch-icon" href="'.$this->coreParametersHelper->getParameter('whitelabel_branding_apple_favicon', 'media/images/apple-touch-icon.png').'" />');
       
    }
}
