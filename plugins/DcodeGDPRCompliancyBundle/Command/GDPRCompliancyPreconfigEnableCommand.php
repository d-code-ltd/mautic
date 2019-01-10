<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use MauticPlugin\DcodeGDPRCompliancyBundle\Integration\GDPRCompliancyIntegration;
use Mautic\PluginBundle\PluginEvents;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class GDPRCompliancyPreconfigEnableCommand extends ContainerAwareCommand
{    
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('gdprcompliancy:preconfig:enable')
            ->setDescription('Whitelabel preconfig plugin enabled.')            
            ->addOption('--dry-run', null, InputOption::VALUE_NONE, 'Do a dry run without actually deleting anything.')            
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to enable plugin by code

<info>php %command.full_name% [--dry-run]</info>
EOT
    );
        
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();        
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $integrationHelper = $container->get('mautic.helper.integration');        
        $integrationObject = $integrationHelper->getIntegrationObject(GDPRCompliancyIntegration::INTEGRATION_NAME);
        
        $integrationRepo = $entityManager->getRepository('MauticPluginBundle:Integration');
        $fieldModel = $entityManager->getModel('MauticLeadBundle:Field');
        
        // Verify that the requested integration exists
        if (!empty($integrationObject)) {
            $integrationSettings = $integrationObject->getIntegrationSettings();
            if (!$integrationSettings->getIsPublished()){

                //do the actual settings 
                $integrationSettings->setIsPublished(true);                

                $currentSupportedFeatures = $integrationSettings->getSupportedFeatures();
                $currentFeatureSettings = $integrationSettings->getFeatureSettings();

                if (empty($currentSupportedFeatures)){
                    $integrationSettings->setSupportedFeatures($integrationObject->getSupportedFeatures());
                }
                if (empty($currentFeatureSettings)){
                    $featureSettings = [
                        'hash_salt' => b_substr(md5(time()),0,16)
                    ];


                    $availableFields = $fieldModel->getLeadFields();
                    foreach ($availableFields as $leadFieldEntity){                
                        if (mb_ereg('_hash$', $leadFieldEntity->getAlias()) AND in_array(mb_ereg_replace('_hash$','',$leadFieldEntity->getAlias()), $integrationObject::$separateHashFields)){
                            continue;
                        }

                   
                        $readonly = false;
                        $defaultValue = $integrationObject::$defaultGDPRFieldBehaviour;

                        $allowedBehaviours = $integrationObject::$GDPRFieldBehaviours;
                        if (in_array($leadFieldEntity->getAlias(),$integrationObject::$fixedHashFields) || in_array($leadFieldEntity->getGroup(),$integrationObject::$fixedHashGroups)){
                            if (!in_array($leadFieldEntity->getType(),$integrationObject::$nonHashableFieldTypes)){
                                $readonly = true;
                                $defaultValue = "hash";
                                unset($allowedBehaviours['keep']);
                                unset($allowedBehaviours['remove']);
                            }else{
                                $defaultValue = "remove";
                                unset($allowedBehaviours['keep']);
                                unset($allowedBehaviours['hash']);
                            }
                        }elseif(in_array($leadFieldEntity->getAlias(),$integrationObject::$fixedKeepFields)){
                            $readonly = true;
                            $defaultValue = "keep";
                            unset($allowedBehaviours['remove']);
                            unset($allowedBehaviours['hash']);
                        }else{
                            if (in_array($leadFieldEntity->getType(),$integrationObject::$nonHashableFieldTypes)){
                                unset($allowedBehaviours['hash']);
                            }    

                            if ($leadFieldEntity->getIsUniqueIdentifer()){
                                unset($allowedBehaviours['hash']);
                            }
                        }
                        $featureSettings[$integrationObject->getFieldSettingKey($leadFieldEntity->getAlias())] = $defaultValue;
                    }
                    
                    $integrationSettings->setFeatureSettings($featureSettings);
                }
                
                $integrationRepo->saveEntity($integrationSettings,true);


                //dispatch PLUGIN_ON_INTEGRATION_CONFIG_SAVE event as if the administrator would have set
                $dispatcher = $container->get('event_dispatcher');                
                if ($dispatcher->hasListeners(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE)) {
                    
                    $event = new PluginIntegrationEvent($integrationObject);

                    $dispatcher->dispatch(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE, $event);

                    $entity = $event->getEntity();
                }

                $entityManager->persist($entity);
                $entityManager->flush();
                
            }
        }else{
            $output->writeln(WhiteLabelIntegration::INTEGRATION_NAME. ' integration not found');
        }
    }    
}
