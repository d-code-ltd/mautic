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
            'location' => $keys['rabbitmq_location'],
            'username' => $keys['rabbitmq_user'],
            'password' => $keys['rabbitmq_password']
        ];
    }

    public function getLocation() {
        $keys = $this->getKeys();

        return $keys['rabbitmq_location'];
    }

    public function getUser() 
    {
        $keys = $this->getKeys();

        return $keys['rabbitmq_user'];
    }

    public function getPassword() 
    {
        $keys = $this->getKeys();

        return $keys['rabbitmq_password'];
    }

    public function getSalesForceData() {
        $keys = $this->getKeys();

        return [
            'client_id' => $keys['salesforce_client_id'],
            'client_secret' => $keys['salesforce_client_secret'],
            'username' => $keys['salesforce_username'],
            'password' => $keys['salesforce_password']
        ];
    }

    public function getSFClientId() {
        $keys = $this->getKeys();

        return $keys['salesforce_client_id'];
    }


    public function getSFClientSecret() {
        $keys = $this->getKeys();

        return $keys['salesforce_client_secret'];
    }


    public function getSFUsername() {
        $keys = $this->getKeys();

        return $keys['salesforce_username'];
    }


    public function getSFPassword() {
        $keys = $this->getKeys();

        return $keys['salesforce_password'];
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
            'rabbitmq_location' => 'mautic.rabbitmq.config.location',
            'rabbitmq_user' => 'mautic.rabbitmq.config.user',
            'rabbitmq_password'  => 'mautic.rabbitmq.config.password',
            'salesforce_client_id' => 'mautic.salesforce.config.client_id',
            'salesforce_client_secret' => 'mautic.salesforce.config.salesforce_client_secret',
            'salesforce_username' => 'mautic.salesforce.config.username',
            'salesforce_password' => 'mautic.salesforce.config.password'
        ];
    }

    /**
     * Defines which key fields are secret from the array returned from getRequiredKeyFields.
     * @return array Array of secret key fields.
     */
    public function getSecretKeys()
    {
        return [
            'rabbitmq_password',
            'salesforce_client_secret',
            'salesforce_password'
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

    /**
     * The field map should be defined here, the keys are the SF field names, while the values are the standardized values in RabbitMQ. 
     * @return array Field map array.
     */
    public function getFieldMapSF() 
    {
        return [
            'Email' => 'email',
            'FirstName' => 'first_name',
            'LastName' => 'last_name',
            'Company' => 'company',
            'MobilePhone' => 'mobile',
            'Gender__c' => 'gender',
            'Birthday__c' => 'birthday',
            'Status' => 'stage'
        ];
    }

    /**
    * The address field map used for populating address object should be defined here, the keys are the SF field names, while the values are the standardized values in RabbitMQ.
    * @return array address field map array
    */

    public function getAddressFieldMapSF(){
        //Commented lines are not defined yet
        return [
            "Country"=>"country",
            //""=>"country_code", 
            "State"=>"state",
            //""=>"state_code",
            //""=>"county",
            "City"=>"city",
            "PostalCode"=>"zip_code",
            "Street"=>"address_line1",
            //"address2"=>"address_line2"
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
    public function formatData($data, $to_standard = true, $ma = true)
    {
        if($ma){
            $fieldMap = $this->getFieldMap();
        } else {
            $fieldMap = $this->getFieldMapSF();
        }

        if(!$to_standard){
            $fieldMap = array_flip($fieldMap);
        }

        $formattedLeadData = array();

        foreach ($data as $key => $value) {
            if(isset($fieldMap[$key])){
                $formattedLeadData[$fieldMap[$key]] = $value;
            }
        }
        if($to_standard)
            $formattedLeadData['address'] = $this->formatAddressData($data, $to_standard, $ma);
        else if(isset($data['address'])){
            $formattedLeadData = array_merge($formattedLeadData, $this->formatAddressData($data['address'], $to_standard, $ma));
        }

        if(!$to_standard && $ma && isset($data['stage'])){
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

        return $formattedLeadData;
        
    }

    public function formatAddressData($data, $to_standard = true, $ma = true)
    {
        if($ma){
            $addressFieldMap = $this->getAddressFieldMap();
        } else {
            $addressFieldMap = $this->getAddressFieldMapSF();
        }

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