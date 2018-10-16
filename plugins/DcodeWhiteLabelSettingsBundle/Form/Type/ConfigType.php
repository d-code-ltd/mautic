<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeWhiteLabelSettingsBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('whitelabel_branding_name', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_name.tooltip',
                ],
            'constraints' => [
                new NotBlank([
                    'message' => 'mautic.core.value.required',
                ]),
            ],
        ]);

        $builder->add('whitelabel_branding_version', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_version',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_version.tooltip',
                ],
            'required' => false,
        ]);

        $builder->add('whitelabel_branding_copyright', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_copyright',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_copyright.tooltip',
                ],
            'required' => false,
        ]);

        $builder->add('whitelabel_branding_favicon', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_favicon',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_favicon.tooltip',
                ],
            'constraints' => [
                new NotBlank([
                    'message' => 'mautic.core.value.required',
                ]),
            ],
        ]);

        $builder->add('whitelabel_branding_apple_favicon', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_apple_favicon',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_apple_favicon.tooltip',
                ],
            'constraints' => [
                new NotBlank([
                    'message' => 'mautic.core.value.required',
                ]),
            ],
        ]);

        $builder->add('whitelabel_branding_logo', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_logo',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_logo.tooltip',
                ],
            'constraints' => [
                new NotBlank([
                    'message' => 'mautic.core.value.required',
                ]),
            ],
        ]);

        $builder->add('whitelabel_branding_left_logo', 'text', [
            'label'      => 'mautic.whitelabel.config.form.whitelabel_branding_left_logo',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.whitelabel.config.form.whitelabel_branding_left_logo.tooltip',
                ],
            'constraints' => [
                new NotBlank([
                    'message' => 'mautic.core.value.required',
                ]),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'whitelabelconfig';
    }
}
