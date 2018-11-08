<?php

return [
    'name'        => 'SenderEngine',
    'description' => 'Enables SenderEngine usage and handling bounced emails',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [            
            'mauticplugin.dcodesenderengine.email.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\ReplaceReturnPathSubscriber',
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
        'api' => [
            'mautic_api_senderenginereport' => [
                'standard_entity' => true,
                'name'            => 'senderengine',
                'path'            => '/senderengine/bounce-report',
                'controller'      => 'DcodeSenderEngineBundle:Api\BounceReportApi',
            ],
        ],
    ],    
    'parameters' => [
       
    ],
];
