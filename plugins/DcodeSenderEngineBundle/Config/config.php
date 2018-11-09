<?php

return [
    'name'        => 'SenderEngine',
    'description' => 'Enables SenderEngine usage and handling bounced emails',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [            
            'mauticplugin.dcodesenderengine.returnpathemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\ReplaceReturnPathSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],            
            'mauticplugin.dcodesenderengine.bouncecallbackemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\BounceCallbackEmailSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
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
