<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Mautic\LeadBundle\Entity\Tag;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class MAConsumerCommand extends ModeratedCommand
{
    private $connection = null;
    private $channel = null;
    private $baseConnectionTry = 0;
    private $callback;
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('rabbitmq:consumer:mautic')
            ->setDescription('RabbitMQ Mautic consumer.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $integrationHelper = $container->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();
        $field_map =  $integrationObject->getFieldMap();

        //Question
        $questionHelper = $this->getHelper('question');
        $questionTryAgain = new ConfirmationQuestion('Try again? (Y/n)', true);

        // Logger
        $logger = $this->getContainer()->get('logger')->withName('AMQP');
        while(true){
            try{
                while($this->connection===null){
                    try{
                        $this->baseConnectionTry++;

                        // Do not proceed if the integration is not enabled.
                        if (false === $integrationObject || !$settings->getIsPublished()) {
                            throw new \Exception("Integration not enabled, check the plugin settings.");
                        }

                        if (empty($integrationObject->getLocation())) {
                            throw new \Exception("RabbitMQ server location not set, check the plugin settings.");
                        }

                        if (empty($integrationObject->getUser())) {
                            throw new \Exception("RabbitMQ user not set, check the plugin settings.");
                        }

                        if (empty($integrationObject->getPassword())) {
                            throw new \Exception("RabbitMQ password not set, check the plugin settings.");
                        }

                        $this->connection = new AMQPSSLConnection(
                            $integrationObject->getLocation(), 
                            $integrationObject->getPort(), 
                            $integrationObject->getUser(), 
                            $integrationObject->getPassword(),
                            $integrationObject->getVirtualHost(),
                            [
                                'cafile'=>getenv("RABBITMQ_SSL_CACERT_FILE"),
                                'local_cert'=>getenv("RABBITMQ_SSL_CERT_FILE"),
                                'local_pk'=>getenv("RABBITMQ_SSL_KEY_FILE"),
                                'verify_peer_name'=>false,
                            ],
                            [
                                "heartbeat"=>1
                            ]);
                        
                        $this->channel = $this->connection->channel();

                        // exchange, type, passive, durable, auto_delete
                        $this->channel->exchange_declare('kiazaki', 'topic', false, true, false);

                        // queue, passive, durable, exclusive, auto_delete
                        $this->channel->queue_declare('mautic.contact', false, true, false, false);

                        // Declare the route_keys to listen to
                        $routing_keys = ['mailengine.contact', 'salesforce.*','kiazaki_ws.*','pimcore.*'];

                        foreach($routing_keys as $routing_key) {
                            $this->channel->queue_bind('mautic.contact', 'kiazaki', $routing_key);
                        }

                        // Declaring new queue which will be for fetching message during which error occured
                        $this->channel->queue_declare('mautic.data.error',false, true, false, false);
                        $this->channel->queue_bind('mautic.data.error', 'kiazaki', 'mautic.contact.error');

                        $this->baseConnectionTry = 0;
                    }catch(\Exception $e){
                        $logger->error("Error occurred while trying to connect to amqp: ".$e->getMessage()."");
                        $output->writeln("<error>Error occurred while trying to connect to amqp: ".$e->getMessage()."</error>");
                        if($this->baseConnectionTry >= 5){
                            if(!$questionHelper->ask($input, $output, $questionTryAgain)){
                                $output->writeln("<error>Consumer aborted!</error>");
                                return;
                            }
                        }
                        $output->writeln("<info>Retrying...</info>");
                        $this->connection = null;
                    }
                }

                $leadModel = $container->get('mautic.lead.model.lead');
                $fieldModel = $container->get('mautic.lead.model.field');

                $stageModel = $container->get('mautic.stage.model.stage');
                $listModel = $container->get('mautic.lead.model.list');

                $output->writeln('<info>[*] Waiting for messages. To exit press CTRL+C</info>');

                $this->callback = function($msg) use ($input, $output, $integrationObject, $leadModel, $fieldModel, $stageModel, $listModel, $questionHelper, $questionTryAgain, $logger) {
                    $repeat = true;
                    $mysql_error_no = 0;
                    while($repeat){
                        $repeat=false;
                        try{
                            $output->writeln("<info>[x] " . date("Y-m-d H:i:s") . " Received message from '" . $msg->delivery_info['routing_key'] . "': " . $msg->body . "</info>");

                            // Decode the message.
                            $leadFields = json_decode($msg->body, true);

                            // Checking entity to see what to update
                            if($leadFields['entity']=='geofence' || $leadFields['entity']=='segment'){
                                // Geofence as segment
                                $list;
                                $gAlias = $leadFields['data']['id'];
                                $gAlias = str_replace(' ', '-', $gAlias);
                                if($leadFields['source']==='kiazaki_ws'){
                                    $gAlias = "geofence-" . $gAlias;
                                }
                                $gName = $leadFields['data']['name'];

                                if($leadFields['operation']=='new' || $leadFields['operation']=='update'){
                                    $list = $listModel->getRepository()->findOneBy(['alias'=>$gAlias]);
                                    //var_dump($list);die;
                                    if($list===null){
                                        $output->writeln("New");
                                        $list = new leadList();
                                    }else{
                                        $output->writeln("Updating");
                                    }
                                    //var_dump($list->getId());die;
                                    $list->setName($gName);
                                    $list->setDescription($gName);
                                    $list->setAlias($gAlias);
                                    $listModel->saveEntity($list, true, false);
                                }else if($leadFields['operation']=='delete'){
                                    $list = $listModel->getRepository()->findOneBy(['alias'=>$gAlias]);
                                    if($list!==null)
                                        $listModel->deleteEntity($list, false);
                                }else{

                                }
                            }
                            else if($leadFields['entity']=='news'){
                                // TODO 

                            }else if($leadFields['entity']=='tag'){
                                if(empty($leadFields['data']['name'])){
                                    throw new \Exception("Message is missing the 'data' part!");   
                                }

                                if($leadFields['operation']==='delete'){
                                    $tag = $leadModel->getTagRepository()->findOneBy(['tag' => $leadFields['data']['name']]);
                                    if($tag){
                                        $leadModel->getTagRepository()->deleteEntity($tag);
                                        $output->writeln("<info>Tag deleted</info>");
                                    }else{
                                        $output->writeln("<info>Tag not found</info>");
                                    }
                                }else if($leadFields['operation']==='update'){
                                    $tag = $leadModel->getTagRepository()->findOneBy(['tag' => $leadFields['data']['old_name']]);
                                    if(!$tag){
                                        $tag = new Tag($leadFields['data']['name']);
                                        $output->writeln("<info>Tag not found so created</info>");
                                    }else{
                                        $tag->setTag($leadFields['data']['name']);
                                        $output->writeln("<info>Tag found</info>");
                                    }
                                    $leadModel->getTagRepository()->saveEntity($tag);
                                    $output->writeln("<info>Tag updated</info>");
                                }else if($leadFields['operation']==='new'){
                                    // new
                                    $tag = new Tag($leadFields['data']['name']);
                                    $leadModel->getTagRepository()->saveEntity($tag);
                                    $output->writeln("<info>New tag saved</info>");
                                }
                            }else{
                                /* If entity is not geofence than update contact. */
                                //$lead = new Lead();
                                //$lead->setNewlyCreated(true);

                                // Check if the data is set.
                                if(isset($leadFields['data'])){
                                    $data = $leadFields['data'];
                                } else {
                                    throw new \Exception("Message is missing the 'data' part!");
                                }
                                // Check if the data is set.
                                if(isset($leadFields['operation'])){
                                    $operation = $leadFields['operation'];
                                } else {
                                    throw new \Exception("Message is missing the 'operation' part!");
                                }

                                // Get the MA unique fields.
                                $uniqueLeadFields = array_keys($fieldModel->getUniqueIdentiferFields());

                                // Convert the data from the standardized format.
                                $data = $integrationObject->formatData($data, false);
                                // Check if the data contains the unique fields.
                                $checkContactWithData = array();
                                foreach ($uniqueLeadFields as $field) {
                                    if(isset($data[$field])) {
                                        $checkContactWithData[$field] = $data[$field];
                                    } else {
                                        throw new \Exception("'$field' field is not defined but is marked as unique!");
                                    }
                                }

                                // Check if a lead exists by the unique fields.
                                $existingLead = $leadModel->getRepository()->getLeadsByUniqueFields($checkContactWithData);

                                if ($operation == 'delete') {
                                    if(!empty($existingLead)){
                                        $leadModel->deleteEntity(reset($existingLead), false);
                                    }
                                } else {
                                    if(!empty($existingLead)){
                                        //$lead = $leadModel->mergeLeads($lead, reset($existingLead), true, false);
                                        $lead = reset($existingLead);
                                    }else{
                                        $lead = new Lead();
                                        $lead->setNewlyCreated(true);
                                    }
                                    // Save the lead.
                                    $leadModel->setFieldValues($lead, $data);

                                    // Adding stage to lead
                                    if(isset($data['stage'])){
                                        $stage = $stageModel->getRepository()->find($data['stage']);
                                        $lead->setStage($stage);
                                    }

                                    $leadModel->saveEntity($lead, true, false);

                                    // Adding lead to segments (fences)
                                    $fenceIds = [];
                                    $fenceNames = [];
                                    // Work with fences only when message is sent from kiazaki_ws
                                    if(isset($leadFields['data']['in_fence']) && $leadFields['source']=="kiazaki_ws" ){
                                        foreach ($leadFields['data']['in_fence'] as $key => $value) {
                                            $fenceNames[] = $value;
                                        }
                                        //Getting lead segments
                                        $leadSegments = $leadModel->getLists($lead);
                                        //Removing only geofence segments from lead
                                        foreach ($leadSegments as $key => $value) {
                                            if(substr($value->getAlias(), 0, 9)=="geofence-"){
                                                $fenceIds[] = $value->getId();   
                                            }
                                        }
                                        $leadModel->removeFromLists($lead, $fenceIds);
                                        //Reseting array
                                        $fenceIds = [];
                                        //Selecting new segments
                                        $fences = $listModel->getRepository()->findBy(['name'=>$fenceNames]);
                                        //Getting segments ids for adding leads to them
                                        foreach ($fences as $key => $value) {
                                            $fenceIds[] = $value->getId();
                                        }
                                        //Adding new segments to lead
                                        $leadModel->addToLists($lead, $fenceIds);
                                    }else if(isset($leadFields['data']['segments']) && $leadFields['source']=="pimcore" ){
                                        foreach ($leadFields['data']['segments'] as $key => $value) {
                                            $fenceNames[] = $value;
                                        }
                                        //Getting lead segments
                                        $leadSegments = $leadModel->getLists($lead);
                                        //Removing only geofence segments from lead
                                        foreach ($leadSegments as $key => $value) {
                                                $fenceIds[] = $value->getId();   
                                        }
                                        $leadModel->removeFromLists($lead, $fenceIds);
                                        //Reseting array
                                        $fenceIds = [];
                                        //Selecting new segments
                                        $fences = $listModel->getRepository()->findBy(['name'=>$fenceNames]);
                                        //Getting segments ids for adding leads to them
                                        foreach ($fences as $key => $value) {
                                            $fenceIds[] = $value->getId();
                                        }
                                        //Adding new segments to lead
                                        $leadModel->addToLists($lead, $fenceIds);
                                    }
                                    if($leadFields['source']=="pimcore"){
                                        if(!isset($leadFields['data']['tags'])){
                                            $leadFields['data']['tags'] = array();
                                            $output->writeln("Tags not found");
                                        }
                                        $newTags = $leadFields['data']['tags'];
                                        
                                        $leadModel->setTags($lead, $newTags);
                                    }
                                }

                            }
                            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                        }
                        catch(\Exception $e){
                            $logger->error('Error:'.$e->getMessage() . ' |Occured while working with message: ' . $msg->body);
                            $output->writeln('<error>Error:'.$e->getMessage()."</error>");

                            // Check if its MySQL connection error
                            if(strpos($e->getMessage(), "2006 MySQL server has gone away")!==false){
                                $output->writeln("<info>Reconnecting to DB</info>");
                                // MySQL server closed connection, reconnect
                                $em = $this->getContainer()->get('doctrine')->getManager();
                                if ($em->getConnection()->ping() === false) {
                                    $em->getConnection()->close();
                                    $em->getConnection()->connect();
                                }
                                $mysql_error_no++;
                                if($mysql_error_no<2){
                                    $repeat = true;
                                    $output->writeln("<info>Reconnected to DB, current message will be repeated.</info>");
                                }
                            }else{
                                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);

                                if($e instanceof AMQPRuntimeException){
                                    // Some error happened with amqp. Reinitialize connection and channed
                                    // Close connection
                                    $this->channel->close();
                                    $this->connection->close();
                                    // Initialize
                                    $this->connection = null;
                                    while($this->connection===null){
                                        try{
                                            $this->baseConnectionTry++;
                                            $this->connection = new AMQPSSLConnection(
                                                $integrationObject->getLocation(), 
                                                $integrationObject->getPort(), 
                                                $integrationObject->getUser(), 
                                                $integrationObject->getPassword(),
                                                $integrationObject->getVirtualHost(),
                                                [
                                                    'cafile'=>getenv("RABBITMQ_SSL_CACERT_FILE"),
                                                    'local_cert'=>getenv("RABBITMQ_SSL_CERT_FILE"),
                                                    'local_pk'=>getenv("RABBITMQ_SSL_KEY_FILE"),
                                                    'verify_peer_name'=>false,
                                                ],
                                                [
                                                    "heartbeat"=>1
                                                ]);
                                            
                                            $this->channel = $this->connection->channel();

                                            // exchange, type, passive, durable, auto_delete
                                            $this->channel->exchange_declare('kiazaki', 'topic', false, true, false);

                                            // queue, passive, durable, exclusive, auto_delete
                                            $this->channel->queue_declare('mautic.contact', false, true, false, false);

                                            // Declare the route_keys to listen to
                                            $routing_keys = ['mailengine.contact', 'salesforce.contact','kiazaki_ws.*','pimcore.*'];

                                            foreach($routing_keys as $routing_key) {
                                                $this->channel->queue_bind('mautic.contact', 'kiazaki', $routing_key);
                                            }
                                            $this->channel->basic_consume('mautic.contact', '', false, false, false, false, $this->callback);
                                            $output->writeln("<info>AMQP Reconnected!</info>");
                                            $this->baseConnectionTry = 0;
                                        }catch(\Exception $e){
                                            if($this->baseConnectionTry==1){
                                                $logger->error('Error:'.$e->getMessage() . ' |Occured while working with message: ' . $msg->body);
                                            }
                                            $output->writeln("<error>Error occurred while trying to connect to amqp: ".$e->getMessage()."</error>");
                                            $output->writeln("<info>Retrying...</info>");
                                            $this->connection = null;
                                        }
                                    }
                                    try{
                                        $this->channel->basic_publish(new AMQPMessage($msg->body), 'kiazaki', 'mautic.contact.error');
                                    }catch(\Exception $e){}
                                }
                            }
                        }
                    }
                };

                $this->channel->basic_consume('mautic.contact', '', false, false, false, false, $this->callback);

                while(count($this->channel->callbacks)) {
                    $this->channel->wait();
                }
            }catch(\Exception $e){
                $logger->error("Error occurred while trying to connect to amqp: ".$e->getMessage()."");
                $output->writeln("<error>------------------------------</error>");
                $output->writeln("<error>".$e->getMessage()."</error>");
                $output->writeln("<error>------------------------------</error>");
                $this->connection = null;
                $this->channel = null;
                $this->baseConnectionTry = 0;
            }
        }
    }
}
