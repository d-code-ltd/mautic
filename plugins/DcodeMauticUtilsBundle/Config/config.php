<?php

return [
    'name'        => 'D-code Mautic Utils',
    'description' => 'Enables things like editing white-label information, importing varying tags, etc',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [
            'mauticplugin.dcodemauticutils.whitelabelsettings.configbundle.subscriber' => [
                'class' => 'MauticPlugin\DcodeMauticUtilsBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.integration'
                ],
            ],
            'mauticplugin.dcodemauticutils.imoprtenhancer.importformbuilder.subscriber' => [
                'class' => 'MauticPlugin\DcodeMauticUtilsBundle\EventListener\ImportFormBuilderSubscriber',
                'arguments' => [
                    'translator',
                    'mauticplugin.dcodegdprcompliancy.repository.lead_event_log',
                    'mautic.helper.integration',
                ],
            ], 
        ],
        'integrations' => [
            'mauticplugin.dcodemauticutils.integration.dcodewhitelabelsettings' => [
                'class'     => \MauticPlugin\DcodeMauticUtilsBundle\Integration\WhiteLabelIntegration::class,
                'arguments' => [
                ],
        	]
    	],
        'forms' => [       
            'mauticplugin.dcodemauticutils.whitelabelconfig.config' => [
                'class'     => 'MauticPlugin\DcodeWMauticUtilsBundle\Form\Type\ConfigType',
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
