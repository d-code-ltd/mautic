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
            'mautic.integration.dcodewhitelabelsettings' => [
                'class'     => \MauticPlugin\DcodeWhiteLabelSettingsBundle\Integration\WhiteLabelIntegration::class,
                'arguments' => [
                ],
        	]
    	]
    ],
    'forms' => [       
        'mautic.form.type.whitelabelconfig' => [
            'class'     => 'MauticPlugin\DcodeWhiteLabelSettingsBundle\Form\Type\ConfigType',
            //'arguments' => 'mautic.factory',
            'arguments' => 'mautic.lead.model.field',
            'alias'     => 'whitelabelconfig',
        ],
        'mauticplugin.form.type.whitelabelconfig' => [
            'class'     => 'MauticPlugin\DcodeWhiteLabelSettingsBundle\Form\Type\ConfigType',
            //'arguments' => 'mautic.factory',
            'arguments' => 'mautic.lead.model.field',
            'alias'     => 'whitelabelconfig2',
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
