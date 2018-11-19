<?php

return [
    'name'        => 'GDPR-compliancy',
    'description' => 'Enables Mautic to operate with GDPR compliancy',
    'author'      => 'peter.osvath@d-code.hu',
    'version'     => '1.0.0',

    'services' => [
        'events' => [            
            /*
            'mauticplugin.dcodegdprcompliancy.returnpath.onsendemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\ReplaceReturnPathSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],            
            'mauticplugin.dcodegdprcompliancy.bouncecallback.onsendemail.subscriber' => [
                'class' => 'MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\BounceCallbackEmailSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'router'
                ],
            ],
            */
           /*
            'mauticplugin.dcodegdprcompliancy.bouncecallback.pluginsave.subscriber' => [
                'class' => 'MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\PluginSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            */
           

           //+ onUserDNCsubscriber

/*           
            'mauticplugin.dcodegdprcompliancy.bouncecallback.timeline_events.subscriber' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\BounceCallbackTimelineEventLogSubscriber::class,
                'arguments' => [
                    'translator',
                    'mauticplugin.dcodegdprcompliancy.repository.lead_event_log',
                    'mautic.helper.integration',                    
                ],
            ],
*/            
        ],
        'integrations' => [
            'mauticplugin.dcodegdprcompliancy.integration.dcodesenderengine' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\GDPRCompliancyIntegration::class,
                'arguments' => [
                ],
        	]
    	],
/*
        'repositories' => [
            'mauticplugin.dcodegdprcompliancy.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadEventLog::class,
                ],
            ],        
        ]
*/
    ],        
    'parameters' => [
       
    ],
];
