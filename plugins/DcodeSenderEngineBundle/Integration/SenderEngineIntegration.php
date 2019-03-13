<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\Integration;

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
class SenderEngineIntegration extends AbstractIntegration
{ 
    const INTEGRATION_NAME         = 'SenderEngine';
    static $defaultValues = [
        'return_path_format' => 'postmaster-{idHash}',
        'return_path_domain' => 'leadengine.hu',
        'bounce3_value' => 3,
        'bounce4_value' => 10,
        'bounce5_value' => 20,
        'bounce_threshold' => 100
    ];

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
    {
        return self::INTEGRATION_NAME;
    }

     /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'SenderEngine';
    }

    public function getDescription()
    {
        return 'SenderEngine handles email bounces via callbacks from SenderEngine senders, adds bounce points to users for each bounce and puts users on Do not Contact list if the set threshold is reached';
    }

    public function getIcon()
    {
        return 'plugins/DcodeSenderEngineBundle/Assets/img/800px_COLOURBOX11423746.jpg';
    }

    public function getSupportedFeatures()
    {
        return [
            'replace_return_path',
            'bounce_callback',            
        ];
    }

    public function getSupportedFeatureTooltips()
    {
        return [
            'replace_return_path'  => 'mautic.plugin.integration.form.features.replace_return_path.tooltip',
            'bounce_callback' => 'mautic.plugin.integration.form.features.bounce_callback.tooltip',
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
    
    /**
     * @return string
     */
    private function getLeadFieldObject()
    {   
        if (class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle')) {
            return 'extendedField';
        }
            
        return 'lead';
    }

    public static $bouncePointsFieldName = 'bounce_points';

    protected function getEnhancerFieldArray()
    {
        return [
            self::$bouncePointsFieldName => [
                'label' => 'Bounce points',
                'type'  => 'number',
            ],
        ];
    }


    public function buildSenderEngineFields(){
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

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
/*        
        if ($formArea == 'keys') {
            $builder->add(
                'service_account_json',
                TextAreaType::class,
                [
                    'label' => 'mautic.plugin.fcmnotification.config.form.notification.service_account',
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.fcmnotification.config.form.notification.service_account.tooltip',
                        'class' => 'form-control',
                        'rows'    => 15,
                    ],
                    'required' => true                    
                ]
            );            
        }
*/        
 
        if ($formArea == 'features') {
            
/*            
            $builder->add(
                'notification_icon',
                TextType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.icon',                    
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.icon.toolip',                        
                    ],
                ]
            );
*/

            $builder->add(
                'return_path_format',
                TextType::class,
                [
                    'label' => 'mautic.plugin.integration.form.features.return_path_format',
                    'attr'  => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.return_path_format.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                        'readonly'     => true
                    ],
                    'required' => true,
                    'empty_data' => self::$defaultValues['return_path_format'],
                    'data' => self::$defaultValues['return_path_format'],
                ]
            );

            $builder->add(
                'return_path_domain',
                TextType::class,
                [
                    'label' => 'mautic.plugin.integration.form.features.return_path_domain',
                    'attr'  => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.return_path_domain.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                        'placeholder'  => self::$defaultValues['return_path_domain'],
                    ],
                    'required' => true,
                    'empty_data' => self::$defaultValues['return_path_domain'],
                ]
            );
            
            $builder->add(
                'bounce3_value',
                NumberType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.bounce_callback.bounce3_value',                    
                    'required' => true,
                    'attr'     => [
                        'class' => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.bounce_callback.bounce3_value.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_1":"checked"}',
                        'placeholder'  => self::$defaultValues['bounce3_value'],
                    ],
                    'empty_data' => self::$defaultValues['bounce3_value'],
                ]
            );

            $builder->add(
                'bounce4_value',
                NumberType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.bounce_callback.bounce4_value',                    
                    'required' => true,
                    'attr'     => [
                        'class' => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.bounce_callback.bounce4_value.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_1":"checked"}',
                        'placeholder'  => self::$defaultValues['bounce4_value'],
                    ],
                    'empty_data' => self::$defaultValues['bounce4_value'],
                ]
            );

            $builder->add(
                'bounce5_value',
                NumberType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.bounce_callback.bounce5_value',                    
                    'required' => true,
                    'attr'     => [
                        'class' => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.bounce_callback.bounce5_value.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_1":"checked"}',
                        'placeholder'  => self::$defaultValues['bounce5_value'],
                    ],
                    'empty_data' => self::$defaultValues['bounce5_value'],
                ]
            );
            
            $builder->add(
                'bounce_threshold',
                NumberType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.bounce_callback.bounce_threshold',                    
                    'required' => true,
                    'attr'     => [
                        'class' => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.bounce_callback.bounce_threshold.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_1":"checked"}',
                        'placeholder'  => self::$defaultValues['bounce_threshold'],
                    ],
                    'empty_data' => self::$defaultValues['bounce_threshold'],
                ]
            );
                
            
/*            
            $builder->add(
                'platforms',
                ChoiceType::class,
                [
                    'choices' => [
                        'ios'     => 'mautic.integration.form.platforms.ios',
                        'android' => 'mautic.integration.form.platforms.android',
                    ],
                    'attr' => [
                        'tooltip'      => 'mautic.integration.form.platforms.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integration.form.platforms',
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
*/            
        }
    }
}