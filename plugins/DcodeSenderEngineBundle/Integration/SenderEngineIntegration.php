<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineSettingsBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

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
            'bounce_report',            
        ];
    }

    public function getSupportedFeatureTooltips()
    {
        return [
            'replace_return_path'  => 'mautic.plugin.integration.form.features.replace_return_path.tooltip',
            'bounce_report' => 'mautic.plugin.integration.form.features.bounce_report.tooltip',
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
                'tracking_page_autoprompt',
                CheckboxType::class,
                [
                    'label' => 'mautic.plugin.integration.form.features.tracking_page_autoprompt',
                    'attr'  => [
                        'class'        => '',
                        'tooltip'      => 'mautic.plugin.integration.form.features.tracking_page_autoprompt.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_3":"checked"}',
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'sample_notification_title',
                TextType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.sample_notification_title',                    
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.sample_notification_title.toolip',
                        'data-show-on' => '{"integration_details_supportedFeatures_3":"checked"}',
                    ],
                ]
            );

            $builder->add(
                'sample_notification_text',
                TextType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.sample_notification_text',                    
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.sample_notification_text.toolip',
                        'data-show-on' => '{"integration_details_supportedFeatures_3":"checked"}',
                    ],
                ]
            );

            $builder->add(
                'welcome_notification_title',
                TextType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.welcome_notification_title',                    
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.welcome_notification_title.toolip',
                        'data-show-on' => '{"integration_details_supportedFeatures_2":"checked"}',
                    ],
                ]
            );

            $builder->add(
                'welcome_notification_text',
                TextType::class,
                [
                    'label'    => 'mautic.plugin.integration.form.features.welcome_notification_text',                    
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'tooltip'      => 'mautic.plugin.integration.form.features.welcome_notification_text.toolip',
                        'data-show-on' => '{"integration_details_supportedFeatures_2":"checked"}',
                    ],
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
