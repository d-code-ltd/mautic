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

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class WhitelabelPreconfigEnableCommand extends ContainerAwareCommand
{    
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('whitelabel:preconfig:enable')
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
        
        $integrationHelper = $this->get('mautic.helper.integration');
        
        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        $integrationSettings = $integrationObject->getIntegrationSettings();
        var_dump($integrationSettings);





/*

        //To prevent hijacking the installation we need to check whether at least one administrator user is present
        try {
            $adminExist = $entityManager->getRepository('MauticUserBundle:User')->find(1);
        } catch (\Exception $e) {
            $adminExist = null;
        }

        if (empty($adminExist) || $input->getOption('dry-run')){
            if (!empty($data)){
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
*/
    }    
}
