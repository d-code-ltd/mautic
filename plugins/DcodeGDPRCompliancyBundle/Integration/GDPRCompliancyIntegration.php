<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class WhiteLabelIntegration.
 */
class GDPRCompliancyIntegration extends AbstractIntegration
{ 
    const INTEGRATION_NAME         = 'GDPRCompliancy';

    /**
     * @var bool
     */
    protected $coreIntegration = false;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {;
        return self::INTEGRATION_NAME;
    }

     /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'GDPR Compliancy';
    }

    public function getDescription()
    {
        return 'GDPR Compliancy plugin makes sure that data of users who got on Do Not Contact list via either bounce, unsubscription or manually are erased from the system leaving only hashed data. Hashed data is required to determine whether an email address was once member of the database while not having the email addresses of unsubscribed users.<br /><br />Hashing algorythm: md5({salt} . {value} . {salt})';
    }

    public function getIcon()
    {
        return 'plugins/DcodeGDPRCompliancyBundle/Assets/img/gdpr-logo.png';
    }

    public function getSupportedFeatures()
    {
        return [
            'do_not_resubscribe_unsubscibed_import',
            'do_not_track_unsubscribed',            
        ];
    }

    public function getSupportedFeatureTooltips()
    {
        return [
            'do_not_resubscribe_unsubscibed_import'  => 'mautic.plugin.integration.form.features.do_not_resubscribe_unsubscibed_import.tooltip',
            'do_not_track_unsubscribed' => 'mautic.plugin.integration.form.features.do_not_track_unsubscribed.tooltip',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
//            'apiKey'        => 'mautic.plugin.fcmnotification.config.form.notification.apikey',            
//            'projectId'  => 'mautic.plugin.fcmnotification.config.form.notification.projectid',
//            'messagingSenderId' => 'mautic.plugin.fcmnotification.config.form.notification.messagingsenderid',            
        ];
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    private function getLeadFieldObject()
    {   
        if (class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle')) {
            return 'extendedField';
        }
            
        return 'lead';
    }

    protected function getEnhancerFieldArray()
    {
        $fieldArray = [];
        foreach (self::$separateHashFields as $fieldAlias){
            $fieldArray[$fieldAlias."_hash"] = [
                'label' => $fieldAlias."_hash",
                'type'  => 'text',
                'is_visible' => false,
                'is_fixed' => true,
                'is_short_visible' => false,
                'is_listable' => false,
                'is_publicly_updatable' => false,
            ];
        }

        return $fieldArray;
    }


    public function buildHashFields(){
        $new_field   = null;
        $integration = $this->getIntegrationSettings();
 
        $existing = $this->fieldModel->getFieldList(false);
        $existing = array_keys($existing);
    
        if ($integration->getIsPublished()) {
            foreach ($this->getEnhancerFieldArray() as $alias => $properties) {
                if (in_array($alias, $existing)) {
                    // The field already exists
                    continue;
                }

                $new_field = new LeadField();
                $new_field->setAlias($alias);
                //setting extendedField/lead in one place,
                $new_field->setObject($this->getLeadFieldObject());

                // Add default required properties to prevent warnings and notices.
                if (isset($properties['type'])) {
                    if ('boolean' === $properties['type']) {
                        $new_field->setProperties('a:2:{s:2:"no";s:2:"No";s:3:"yes";s:3:"Yes";}');
                    } elseif ('number' === $properties['type']) {
                        $new_field->setProperties('a:2:{s:9:"roundmode";s:1:"3";s:9:"precision";s:0:"";}');
                    }
                }
                foreach ($properties as $property => $value) {
                    //convert snake case to cammel case
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $property)));

                    try {
                        $new_field->$method($value);
                    } catch (\Exception $e) {
                        error_log('Failed with "'.$e->getMessage().'"');
                    }
                }
                try {
                    $this->em->persist($new_field);
                    $this->em->flush($new_field);
                } catch (OptimisticLockException $e) {
                    $this->logger->warning($e->getMessage());
                }
            }
        }
    }


    public static $defaultGDPRFieldBehaviour = 'remove';

    public static $fixedHashFields = [
        'firstname', 'lastname', 'company', 'email', 'mobile', 'phone', 'fax', 'address1', 'address2', 
    ];
    public static $fixedKeepFields = [
        'bounce_points', 
    ];
    public static $fixedHashGroups = [
        'social', 
    ];
    
    public static $nonHashableFieldTypes = [
        'number', 'datetime', 'date', 'time', 'timezone',
    ];
    
    public static $GDPRFieldBehaviours = [
        'keep'         => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.keep',
        'remove'       => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.remove',
        'hash'     => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.hash',                        
    ];

    public static $separateHashFields = ['email'];

    public function getFieldSettingKey($alias){
        if (!empty($alias)){
            return $alias.'-gdpr_behaviour';
        }else{
            return false;
        }
    }

    
    

    public function hashValue($value, $salt){
        return md5($salt.$value.$salt);
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
   
            $builder->add(
                'hash_salt',
                TextType::class,
                [
                    'label' => 'mautic.plugin.integration.form.features.hash_salt',
                    'attr'  => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.hash_salt.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                        'readonly'     => true
                    ],
                    'required' => true,                        
                    'empty_data' => mb_substr(md5(time()),0,16)                        
                ]
            );

            $availableFields = $this->fieldModel->getLeadFields();
            foreach ($availableFields as $leadFieldEntity){                
                if (mb_ereg('_hash$', $leadFieldEntity->getAlias()) AND in_array(mb_ereg_replace('_hash$','',$leadFieldEntity->getAlias()), self::$separateHashFields)){
                    continue;
                }

           
                $readonly = false;
                $defaultValue = self::$defaultGDPRFieldBehaviour;

                $allowedBehaviours = self::$GDPRFieldBehaviours;
                if (in_array($leadFieldEntity->getAlias(),self::$fixedHashFields) || in_array($leadFieldEntity->getGroup(),self::$fixedHashGroups)){
                    if (!in_array($leadFieldEntity->getType(),self::$nonHashableFieldTypes)){
                        $readonly = true;
                        $defaultValue = "hash";
                        unset($allowedBehaviours['keep']);
                        unset($allowedBehaviours['remove']);
                    }else{
                        $defaultValue = "remove";
                        unset($allowedBehaviours['keep']);
                        unset($allowedBehaviours['hash']);
                    }
                }elseif(in_array($leadFieldEntity->getAlias(),self::$fixedKeepFields)){
                    $readonly = true;
                    $defaultValue = "keep";
                    unset($allowedBehaviours['remove']);
                    unset($allowedBehaviours['hash']);
                }else{
                    if (in_array($leadFieldEntity->getType(),self::$nonHashableFieldTypes)){
                        unset($allowedBehaviours['hash']);
                    }    

                    if ($leadFieldEntity->getIsUniqueIdentifer()){
                        unset($allowedBehaviours['hash']);
                    }
                }
            
                $builder->add(
                    $this->getFieldSettingKey($leadFieldEntity->getAlias()),
                    'choice',
                    [
                        'label'    => $leadFieldEntity->getLabel(),
                        'choices'  => $allowedBehaviours,
                        'required' => false,
                        'attr'     => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.tooltip',                     
                            'readonly' => $readonly
                        ],
                        'expanded'    => false,
                        'multiple'    => false,
                        'preferred_choices' => [$defaultValue], //default behaviour
                        'required'    => true,                        
                    ]
                );                
            }           
        }        
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ('features' === $section) {
           return ['mautic.plugin.gdprcompliancy.features.notes', 'warning'];
        }

        return parent::getFormNotes($section);        
    }
}
