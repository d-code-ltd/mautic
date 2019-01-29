<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE     => ['onLeadPostDelete', 0],
            LeadEvents::LIST_POST_SAVE     => ['onListPostSave', 0],
            LeadEvents::LIST_POST_DELETE     => ['onListPostDelete', 0],
            LeadEvents::TAG_POST_SAVE       => ['onTagPostSave', 0]
        ];
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        foreach (debug_backtrace() as $key => $debug) {
            if(strpos($debug['class'], "MAConsumerCommand") !== false){
                return;
            }
        }

        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $lead = $event->getLead()->convertToArray();
        $settings = $integrationObject->getIntegrationSettings();


        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }


        // The main array contains only the defaults fields, the custom ones will be listed in the 'field' key
        $leadData = array();
        foreach ($lead['fields'] as $group) {
            foreach ($group as $key => $value) {
                $leadData[$key] = $value['value'];
            }
        }

        //Adding stage name field separated because stage is merged to lead trough stage_id
        if(!empty($lead['stage']))
            $leadData['stage'] = $lead['stage']->getName();
        else
            $leadData['stage'] = '';

        $tags = array();
        if(!empty($lead['tags'])){
            foreach ($lead['tags'] as $key => $tag) {
                $tags[] = $tag->getTag();
            }
        }

        $leadData = $integrationObject->formatData($leadData);
        $leadData['tags'] = $tags;

        // Get lead segments
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');
        $segments = $repo->getLeadLists($lead['id']);

        $leadSegments = array();
        foreach ($segments as $key => $value) {
            $leadSegments[] = $value->getName();
        }

        $leadData['segments'] = $leadSegments;

        // Email is primary key, so if its not set don't send anything to RabbitMQ. (Helps with some unexpected event triggering)
        if(!empty($leadData['email'])){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "contact",
                "operation" => $event->isNew() ? 'new' : 'update',
                "data" => $leadData
            ]);

            $this->publish($data);
        }
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }


        $lead = $event->getLead();
        // Email is primary key, so if its not set don't send anything to RabbitMQ. (Helps with some unexpected event triggering)
        if(!empty($lead->getEmail())){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "contact",
                "operation" => "delete",
                "data" => [
                    'email' => $lead->getEmail()
                ]
            ]);

            $this->publish($data);
        }
    }

    public function onListPostSave(Events\LeadListEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }

        $list = $event->getList();

        // Geofence segments are not supposed to be updated trough mautic
        if(substr($list->getAlias(), 0, 9) !== 'geofence'){
            if(!empty($list->getAlias()) && !empty($list->getName())){
                $data = json_encode([
                    "source" => "mautic",
                    "entity" => "segment",
                    "operation" => $event->isNew() ? 'new' : 'update',
                    "data" => array(
                        'id' => $list->getAlias(),
                        'name' => $list->getName()
                    )
                ]);
                
                $this->publish($data, 'list');
            }
        }
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onListPostDelete(Events\LeadListEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }


        $list = $event->getList();
        if(!empty($list->getAlias()) && !empty($list->getName())){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "segment",
                "operation" => "delete",
                "data" => [
                    'id' => $list->getAlias()
                ]
            ]);

            $this->publish($data, 'list');
        }
    }

    public function onTagPostSave(Events\TagEvent $event){
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }

        $tag = $event->getTag();
        
        if(!empty($tag->getTag())){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "tag",
                "operation" => $event->isNew() ? "new" : "update",
                "data" => [
                    "name" => $tag->getTag()
                ]
            ]);
            $this->publish($data, 'tag');
        }
    }

    /**
     * @param array $data The data/message to be sent.
     */
    private function publish($data, $dataType='contact'){
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }

        $connection = new AMQPSSLConnection(
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
            ]);
        $channel = $connection->channel();

        // exchange, type, passive, durable, auto_delete
        $channel->exchange_declare('kiazaki', 'topic', false, true, false);

        $msg = new AMQPMessage($data);

        switch ($dataType) {
            case 'list':
                $channel->basic_publish($msg, 'kiazaki', 'mautic.segment');
                break;
            case 'tag':
                $channel->basic_publish($msg, 'kiazaki', 'mautic.tag');
                break;
            
            default:
                $channel->basic_publish($msg, 'kiazaki', 'mautic.contact');
                break;
        }

        $channel->close();
        $connection->close();
    }
}
