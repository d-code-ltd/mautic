<?php

return [
    'name'        => 'White-label',
    'description' => 'Enables editing white-label information',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [
            'mauticplugin.dcodewhitelabelsettings.configbundle.subscriber' => [
                'class' => 'MauticPlugin\DcodeWhiteLabelSettingsBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.integration'
                ],
            ],
        ],
        'integrations' => [
            'mauticplugin.dcodewhitelabelsettings.integration.dcodewhitelabelsettings' => [
                'class'     => \MauticPlugin\DcodeWhiteLabelSettingsBundle\Integration\WhiteLabelIntegration::class,
                'arguments' => [
                ],
        	]
    	],
        'forms' => [       
            'mauticplugin.dcodewhitelabelsettings.whitelabelconfig.config' => [
                'class'     => 'MauticPlugin\DcodeWhiteLabelSettingsBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',            
                'alias'     => 'whitelabelconfig',
            ],
        ], 
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
