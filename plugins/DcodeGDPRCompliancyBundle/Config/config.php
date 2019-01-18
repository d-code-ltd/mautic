<?php

return [
    'name'        => 'GDPR compliancy',
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
            
            'mauticplugin.dcodegdprcompliancy.pluginsave.subscriber' => [
                'class' => 'MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\PluginSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead'
                ],
            ],             
            'mauticplugin.dcodegdprcompliancy.ondonotcontact.channelsubscriptionchange.subscriber' => [
                'class' => 'MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\GDPRCompliancyChannelSubscriptionChangeSubscriber',
                'arguments' => [
                    'translator',
                    'monolog.logger.mautic',
                    'mauticplugin.dcodegdprcompliancy.repository.lead_event_log',
                    'mautic.helper.integration',
                    'mautic.lead.model.field',
                    'mautic.lead.model.lead'
                ],
            ],  
            'mauticplugin.dcodegdprcompliancy.timeline_events.subscriber' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\GDPRCompliancyTimelineEventLogSubscriber::class,
                'arguments' => [
                    'translator',
                    'mauticplugin.dcodegdprcompliancy.repository.lead_event_log',
                    'mautic.helper.integration',                    
                ],
            ],
            'mauticplugin.dcodegdprcompliancy.search.subscriber' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.integration',
                ],
            ],
            'mauticplugin.dcodegdprcompliancy.leadtracker.identified.subscriber' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\LeadTrackerIdentifiedSubsciber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead'
                ],
            ],
            'mauticplugin.dcodegdprcompliancy.leadimport.identified.subscriber' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\EventListener\LeadImportIdentifySubsciber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead'
                ],
            ],
        ],
        'integrations' => [
            'mauticplugin.dcodegdprcompliancy.integration.dcodegdprcompliancy' => [
                'class'     => \MauticPlugin\DcodeGDPRCompliancyBundle\Integration\GDPRCompliancyIntegration::class,
                'arguments' => [
                ],
        	]
    	],

        'repositories' => [
            'mauticplugin.dcodegdprcompliancy.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadEventLog::class,
                ],
            ],        
        ],
/*
        'models' => [
            'mauticplugin.dcodegdprcompliancy.lead.model.field' => [
                'class'     => 'Mautic\LeadBundle\Model\FieldModel',
                'arguments' => [
                    'mautic.schema.helper.index',
                    'mautic.schema.helper.column',
                ],
            ],
        ]
*/
    ],        
    'parameters' => [
       
    ],
];