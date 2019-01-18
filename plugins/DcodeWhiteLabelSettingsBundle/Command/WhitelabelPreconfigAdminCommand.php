<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeWhiteLabelSettingsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mautic\UserBundle\Entity\User;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class WhitelabelPreconfigAdminCommand extends ContainerAwareCommand
{    
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('whitelabel:preconfig:admin')
            ->setDescription('Whitelabel preconfig administrator users.')
            ->addArgument('data', InputArgument::REQUIRED, 'Data?')
            ->addOption('--dry-run', null, InputOption::VALUE_NONE, 'Do a dry run without actually deleting anything.')            
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to create administrator users

<info>php %command.full_name% [--dry-run] -- [data]</info>
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
        $configurator = new Configurator($container->get('mautic.helper.paths'));
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        //Reload plugins dir
        $pluginReloadFacade = $container->get('mautic.plugin.facade.reload');
        $pluginReloadFacade->reloadPlugins();
        
        //Collect input data
        $params = $configurator->getParameters();
        $data = $input->getArgument('data');
    
        //To prevent hijacking the installation we need to check whether at least one administrator user is present
        try {
            //$adminExist = $entityManager->getRepository('MauticUserBundle:User')->find(1);
            $adminExist = $entityManager->getRepository('MauticUserBundle:User')->getUserList(null,1);            
        } catch (\Exception $e) {
            $adminExist = null;
        }

        var_dump($adminExist);











        if (empty($adminExist) || $input->getOption('dry-run')){
            if (!empty($data)){


                //Install 
                if ($input->getOption('dry-run')){
                    $output->writeln('running dry: database setup, installSchema');   
                }else{
                    $db_params = [];
                    foreach ($params as $k => $p){
                        if (mb_ereg('^db_',$k)){
                            $db_params[str_replace('db_','',$k)] = $p;
                        }
                    }
                    

                    $schemaHelper = new SchemaHelper($db_params);
                    $schemaHelper->setEntityManager($container->get('doctrine.orm.entity_manager'));

                    
                    try {
                        $schemaHelper->testConnection();
                        $output->writeln('Creating database...');
                        if ($schemaHelper->createDatabase()) {
                            $output->writeln('Database created'); 
                        } else {
                            $output->writeln('mautic.installer.error.creating.database');                             
                        }
                    } catch (\Exception $exception) {
                        $output->writeln('Preinstaller failed during database setup'); 
                        $output->writeln($exception->getMessage());                      
                    }

                    try {
                        $output->writeln('Installing schema');
                        $schemaHelper->installSchema();
                    } catch (\Exception $exception) {    
                        $output->writeln('Preinstaller failed during installSchema'); 
                        $output->writeln($exception->getMessage());                      
                    }

                    try {
                        $output->writeln('Installing database fixtures');
                        $this->installDatabaseFixtures();
                    } catch (\Exception $exception) {    
                        $output->writeln('Preinstaller failed during installSchema'); 
                        $output->writeln($exception->getMessage());                      
                    }

                    $finalConfigVars = [
                        'secret_key' => EncryptionHelper::generateKey(),                
                    ];

                    $configurator->mergeParameters($finalConfigVars);

                    try {
                        $configurator->write();
                    } catch (\RuntimeException $exception) {
                        $output->writeln('writing config file failed');
                    }

                    
                    //$container->get('mautic.helper.cache')->clearContainerFile(false);
                    

                    //applying migrations
                    $consoleInput  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
                    $consoleOutput = new BufferedOutput();

                    $application = new Application($container->get('kernel'));
                    $application->setAutoExit(false);
                    $application->run($consoleInput, $consoleOutput);                    
                }




                $dataArray = explode("|", $data);
                if (is_array($dataArray)){
                    foreach ($dataArray as $adminData){
                        $adminData = trim($adminData);
                        $adminArray = explode(",", $adminData);
                        if (is_array($adminArray)){
                            if (count($adminArray) == 5){                        
                                $user = new User();
                                $encoder = $container->get('security.encoder_factory')->getEncoder($user);

                                $validator = $container->get('validator');
                                $constraints = array(
                                    new \Symfony\Component\Validator\Constraints\Email(),
                                    new \Symfony\Component\Validator\Constraints\NotBlank()
                                );

                                $error = $validator->validateValue($adminArray[3], $constraints);

                                if (count($error)==0){
                                    if (!empty(adminArray[4])){
                                        if ($input->getOption('dry-run')){
                                            $output->writeln("username: {$adminArray[2]}");  
                                            $output->writeln("firstname: {$adminArray[0]}");  
                                            $output->writeln("lastname: {$adminArray[1]}");  
                                            $output->writeln("email: {$adminArray[3]}");  
                                            $output->writeln("password: ".$encoder->encodePassword($adminArray[5], $user->getSalt()));  
                                        }else{                                    
                                            $user->setFirstName($adminArray[0]);
                                            $user->setLastName($adminArray[1]);
                                            $user->setUsername($adminArray[2]);
                                            $user->setEmail($adminArray[3]);
                                            $user->setPassword($encoder->encodePassword($adminArray[4], $user->getSalt()));
                                            $user->setRole($entityManager->getReference('MauticUserBundle:Role', 1));

                                            $entityManager->persist($user);
                                            $entityManager->flush();    
                                        }  
                                    }else{
                                        $output->writeln('password can not be blank');            
                                    }
                                }else{
                                    $output->writeln($adminArray[3].' email is malformed or empty');        
                                }                         
                            }else{
                                $output->writeln($adminData.' has wrong number of parameters. Should be: username,firstname,lastname,email,password');        
                            }
                        }else{
                            $output->writeln($adminData.' is not explodeable by ;');        
                        }
                    }
                }else{
                    $output->writeln('The data provided is not explodeable by |');
                }    
            }else{
                $output->writeln('No data provided');
            }
            
        }else{
            $output->writeln('This Mautic installation already has Administrator configured');
        }
    }   

    /**
     * Installs data fixtures for the application.
     * COPIED FROM app/InstallBundle/Controller/InstallController.php
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     */
    private function installDatabaseFixtures()
    {
        $container = $this->getContainer();     

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pathsHelper = $container->get('mautic.helper.paths');

        var_dump($pathsHelper->getSystemPath("_root"));
        #GET InstallBundle PATH!%!!!!!!!!
        $paths         = [$pathsHelper->getSystemPath("_root").'/app/bundles/InstallBundle/InstallFixtures/ORM'];
        $loader        = new ContainerAwareLoader($container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $purger = new ORMPurger($entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($fixtures, true);
    } 
}
