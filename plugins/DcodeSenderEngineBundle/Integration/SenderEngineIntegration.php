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
        return 'SenderEngine';
    }

     /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'SenderEngine';
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
    
    protected function getEnhancerFieldArray()
    {
        return [
            'bounce_points' => [
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
                    ],
                    'required' => false,
                    'empty_data' => 'postmaster-{idHash}@example.com'
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
                    ],
                    'empty_data' => '3'
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
                    ],
                    'empty_data' => '10'
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
                    ],
                    'empty_data' => '20'
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
                    ],
                    'empty_data' => '100'
                ]
            );
            
            $builder->add(
                'bounce_unsubscribe',
                CheckboxType::class,
                [
                    'label' => 'mautic.plugin.integration.form.features.bounce_callback.bounce_unsubscribe',
                    'attr'  => [
                        'class'        => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.bounce_callback.bounce_unsubscribe',
                        'data-show-on' => '{"integration_details_supportedFeatures_1":"checked"}',
                    ],
                    'required' => false,
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
