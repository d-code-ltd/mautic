<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class RabbitMQIntegration
 */
class RabbitMQIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'RabbitMQ';
    }

    public function getDisplayName()
    {
        return $this->getName();
    }

    public function getRabbitMQData() {
        $keys = $this->getKeys();

        return [
            // 'location' => $keys['rabbitmq_location'],
            // 'username' => $keys['rabbitmq_user'],
            // 'password' => $keys['rabbitmq_password']
        ];
    }

    public function getLocation() {
        // $keys = $this->getKeys();

        // return $keys['rabbitmq_location'];
        return getenv("MQ_HOST");
    }

    public function getUser() 
    {
        // $keys = $this->getKeys();

        // return $keys['rabbitmq_user'];
        return getenv("MQ_USERNAME");
    }

    public function getPassword() 
    {
        // $keys = $this->getKeys();

        // return $keys['rabbitmq_password'];
        return getenv("MQ_PASSWORD");
    }

    public function getPort()
    {
        $port = getenv("MQ_PORT");
        if($port === false || empty($port))
            return 5672;
        return $port;
    }

    public function getVirtualHost()
    {
        $virtualHost = getenv("MQ_VIRTUAL_HOST");
        if($virtualHost === false || empty($virtualHost))
            return "/";
        return $virtualHost;
    }

    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * Defines the additional key fields required by the plugin.
     * @return array Array of key fields.
     */
    public function getRequiredKeyFields()
    {
        return [
            // 'rabbitmq_location' => 'mautic.rabbitmq.config.location',
            // 'rabbitmq_user' => 'mautic.rabbitmq.config.user',
            // 'rabbitmq_password'  => 'mautic.rabbitmq.config.password'
        ];
    }

    /**
     * Defines which key fields are secret from the array returned from getRequiredKeyFields.
     * @return array Array of secret key fields.
     */
    public function getSecretKeys()
    {
        return [
            // 'rabbitmq_password'
        ];
    }

    /**
     * The field map should be defined here, the keys are the MA field names, while the values are the standardized values in RabbitMQ. 
     * @return array Field map array.
     */
    public function getFieldMap() 
    {
        return [
            'email' => 'email',
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'mobile' => 'mobile',
            'gender' => 'gender',
            'birthday' => 'birthday',
            'points' => 'points',
            'stage' => 'stage'
        ];
    }

    /**
    * The address field map used for populating address object should be defined here, the keys are the MA field names, while the values are the standardized values in RabbitMQ.
    * @return array address field map array
    */

    public function getAddressFieldMap(){
        //Commented lines are not defined yet
        return [
            "country"=>"country",
            //""=>"country_code", 
            "state"=>"state",
            //""=>"state_code",
            //""=>"county",
            "city"=>"city",
            "zipcode"=>"zip_code",
            "address1"=>"address_line1",
            "address2"=>"address_line2"
        ];
    }

    public function getListFieldMap(){
        return [
            'alias'=>'id',
            'name'=>'name'
        ];
    }

    /**
     * Format the lead data to the structure that RabbitMQ requires.
     *
     * @param array The data we want to format.
     * @param bool Set to true if you want to convert MA format to RabbitMQ format. Set to false if you want to convert RabbitMQ format to MA format.
     * 
     * @return array
     */
    public function formatData($data, $to_standard = true, $dataType = 'contact')
    {
        $fieldMap = null;
        switch ($dataType) {
            case 'list':
                $fieldMap = $this->getListFieldMap();
                break;
            // default == contact
            default:
                $fieldMap = $this->getFieldMap();
                break;
        }
        $fieldMap = $this->getFieldMap();

        if(!$to_standard){
            $fieldMap = array_flip($fieldMap);
        }

        $formattedLeadData = array();

        foreach ($data as $key => $value) {
            if(isset($fieldMap[$key])){
                $formattedLeadData[$fieldMap[$key]] = $value;
            }
        }
        if($dataType==='contact'){
            if($to_standard)
                $formattedLeadData['address'] = $this->formatAddressData($data, $to_standard);
            else if(isset($data['address'])){
                $formattedLeadData = array_merge($formattedLeadData, $this->formatAddressData($data['address'], $to_standard));
            }

            if(!$to_standard && isset($data['stage'])){
                if(isset($data['stage'])){
                    $stages = $this->em->getRepository('MauticStageBundle:Stage')->findBy(['name'=>$data['stage']]);
                    
                    if(count($stages)>0){
                        $formattedLeadData['stage']=$stages[0]->getId();
                    }else{
                        unset($formattedLeadData['stage']);
                    }
                }else{
                    unset($formattedLeadData['stage']);
                }
            }
        }
        
        return $formattedLeadData;
        
    }

    public function formatAddressData($data, $to_standard = true)
    {

        $addressFieldMap = $this->getAddressFieldMap();

        if(!$to_standard){
            $addressFieldMap = array_flip($addressFieldMap);
        }

        $formattedLeadData = array();

        foreach ($data as $key => $value) {
            if(isset($addressFieldMap[$key])){
                $formattedLeadData[$addressFieldMap[$key]] = $value;
            }
        }

        return $formattedLeadData;
    }
}