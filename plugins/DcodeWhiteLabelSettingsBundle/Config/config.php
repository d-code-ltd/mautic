<?php

return [
    'name'        => 'Whitelabel',
    'description' => 'Enables editing whitelabel information',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [
            'mautic.dcodewhitelabelsettings.configbundle.subscriber' => [
                'class' => 'MauticPlugin\DcodeWhiteLabelSettingsBundle\EventListener\ConfigSubscriber',
            ],
        ],
        'integrations' => [
            'mautic.integration.rabbitmq' => [
                'class'     => \MauticPlugin\RabbitMQBundle\Integration\WhiteLabelIntegration::class,
                'arguments' => [
                ],
        	]
    	]
    ], 
    'parameters' => [
        'whitelabel_branding_name' => '',
        'whitelabel_branding_version' => '',
        'whitelabel_branding_copyright' => '',
        'whitelabel_branding_favicon' => '',
        'whitelabel_branding_apple_favicon' => '',
        'whitelabel_branding_logo' => '',
        'whitelabel_branding_left_logo' => '',
    ],
];
