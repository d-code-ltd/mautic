<?php

return [
    'name'        => 'SenderEngine',
    'description' => 'Enables SenderEngine usage and handling bounced emails',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [            
            'mauticplugin.dcodesenderengine.returnpath.onsendemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\ReplaceReturnPathSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],            
            'mauticplugin.dcodesenderengine.bouncecallback.onsendemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\BounceCallbackEmailSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'router'
                ],
            ],
            'mauticplugin.dcodesenderengine.bouncecallback.pluginsave.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\PluginSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            'mauticplugin.dcodesenderengine.bouncecallback.timeline_events.subscriber' => [
                'class'     => \MauticPlugin\DcodeSenderEngineBundle\EventListener\BounceCallbackTimelineEventLogSubscriber::class,
                'arguments' => [
                    'translator',
                    'mauticplugin.dcodesenderengine.repository.lead_event_log',
                    'mautic.helper.integration',                    
                ],
            ],
            
        ],
        'integrations' => [
            'mauticplugin.dcodesenderengine.integration.dcodesenderengine' => [
                'class'     => \MauticPlugin\DcodeSenderEngineBundle\Integration\SenderEngineIntegration::class,
                'arguments' => [
                ],
        	]
    	],
        'repositories' => [
            'mauticplugin.dcodesenderengine.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadEventLog::class,
                ],
            ],        
        ],
    ],    
    'routes' => [        
        'public' => [
            'mauticplugin.dcodesenderengin.route.bouncecallback' => [                
                'path'       => '/senderengine/bouncecallback/{idHash}',
                'controller' => 'DcodeSenderEngineBundle:BounceCallback:callback',
            ],
        ],
    ],    
    'parameters' => [
       
    ],
];
