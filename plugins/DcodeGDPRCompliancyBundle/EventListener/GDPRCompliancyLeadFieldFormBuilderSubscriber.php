<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeGDPRCompliancyBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\CoreBundle\Event\CustomFormEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Mautic\CoreBundle\CoreEvents;


use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;

/**
 * Class WebhookSubscriber.
 */
class GDPRCompliancyLeadFieldFormBuilderSubscriber extends CommonSubscriber
{
    /**
     * @var TranslatorInterface|Translator
     */
    public $translator;

    /**
     * @var LeadEventLogRepository
     */
    protected $leadEventLogRepository;

     /**
     * @var integrationHelper
     */
    protected $integrationHelper;

    /**
     * TimelineEventLogSubscriber constructor.
     *
     * @param TranslatorInterface    $translator
     * @param ModelFactory           $modelFactory
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        LeadEventLogRepository $LeadEventLogRepository,
        IntegrationHelper $integrationHelper
    ) {
        $this->translator             = $translator;
        $this->leadEventLogRepository = $LeadEventLogRepository;
        $this->integrationHelper      = $integrationHelper;      
    }     


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [            
            CoreEvents::ON_FORM_TYPE_BUILD => ['onLeadFieldFormBuild', 0],
        ];
    }

    /**
     * @param ChannelSubscriptionChange $event
     */
    public function onLeadFieldFormBuild(CustomFormEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        $formPrepare = function (FormEvent $event) {
            $cleaningRules = [];
            $form          = $event->getForm();
            $data          = $event->getData();

            if (is_array($data)) {
                $properties = isset($data['properties']) ? $data['properties'] : [];
            } else {
                $properties = $data->getProperties();
            }

            $form->add(
                'field_gdpr_behaviour',
                'choice',
                [
                    'choices' => [
                        'keep'         => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.keep',
                        'remove'       => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.remove',
                        'hash'     => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.hash',                        
                    ],
                    'attr' => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle.toolip',
                    ],
                    'expanded'    => false,
                    'multiple'    => false,
                    'label'       => 'mautic.plugin.gdprcompliancy.leadfieldform.unsubscribe_handle',
                    'empty_value' => false,
                    'required'    => false,
                    'disabled'    => false,
                    "mapped"      => false,
                    'data'        => !empty($properties['field_gdpr_behaviour'])?$properties['field_gdpr_behaviour']:'keep'
                ]
            );
        };    




        if ($event->getFormName() == 'leadfield'){
            $event->getFormBuilder()->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formPrepare) {
                    $formPrepare($event);
                }
            );
        }

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event){
                $data          = $event->getData();
                
                $data['properties']['field_gdpr_behaviour'] = $data['field_gdpr_behaviour'];

                $event->setData($data);
            }
        );        
    }
}
