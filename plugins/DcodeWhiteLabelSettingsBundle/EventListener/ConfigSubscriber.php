<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeWhiteLabelSettingsBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper       $integrationHelper    
     */
    public function __construct(
        IntegrationHelper $integrationHelper        
    ) {
        $this->integrationHelper = $integrationHelper;        
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {   
        $integration = $this->integrationHelper->getIntegrationObject('WhiteLabel');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }


        $event->addForm([
            //'bundle'     => 'DcodeWhiteLabelSettingsBundle',
            'formAlias'  => 'whitelabelconfig',
            'formTheme'  => 'DcodeWhiteLabelSettingsBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('DcodeWhiteLabelSettingsBundle'),
        ]);
    }
}
