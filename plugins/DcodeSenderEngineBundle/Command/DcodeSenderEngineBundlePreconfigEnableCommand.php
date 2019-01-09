<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use MauticPlugin\DcodeSenderEngineBundle\Integration\SenderEngineIntegration;
use Mautic\PluginBundle\PluginEvents;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class DcodeSenderEngineBundlePreconfigEnableCommand extends ContainerAwareCommand
{    
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('senderengine:preconfig:enable')
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
        $integrationObject = $integrationHelper->getIntegrationObject(SenderEngineIntegration::INTEGRATION_NAME);
        
        $integrationRepo = $entityManager->getRepository('MauticPluginBundle:Integration');
        
        // Verify that the requested integration exists
        if (!empty($integrationObject)) {
            $integrationSettings = $integrationObject->getIntegrationSettings();

            var_dump($integrationSettings->getSupportedFeatures());
            var_dump($integrationSettings->getFeatureSettings());

            if (!$integrationSettings->getIsPublished()){

                //do the actual settings 
                $integrationSettings->setIsPublished(true);                

                $integrationSettings->setSupportedFeatures($integrationObject->getSupportedFeatures());
                $integrationSettings->setFeatureSettings($integrationObject::$defaultValues);

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
