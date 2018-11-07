<?php

return [
    'name'        => 'SenderEngine',
    'description' => 'Enables SenderEngine usage and handling bounced emails',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [
            /*
            'mauticplugin.dcodessenderenginesettings.configbundle.subscriber' => [
                'class' => 'MauticPlugin\DcodeSenderEngineBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.integration'
                ],
            ],
            */
            //sendEmail event -> return path cseréje
            
        ],
        'integrations' => [
            'mauticplugin.dcodewhitelabelsettings.integration.dcodessenderenginesettings' => [
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
