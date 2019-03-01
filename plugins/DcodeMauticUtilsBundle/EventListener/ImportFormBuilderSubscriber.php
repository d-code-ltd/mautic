<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeMauticUtilsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\CoreBundle\Event\CustomFormEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\CoreBundle\CoreEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;

/**
 * Class WebhookSubscriber.
 */
class ImportFormBuilderSubscriber extends CommonSubscriber
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
            CoreEvents::ON_FORM_TYPE_BUILD => ['onImportFormBuild', 0],
        ];
    }

    /**
     * @param ChannelSubscriptionChange $event
     */
    public function onImportFormBuild(CustomFormEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('ImportEnhancer');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        if ($event->getFormName() == 'lead_field_import'){
            $formPrepare = function (FormEvent $event) {                
                $form          = $event->getForm();
                foreach ($form->all() as $key => $child){
                    var_dump($key, $child->getName());                    
                    var_dump($child->getConfig()->getOption('choices'));                    
                }            
                exit;
            };     

            $event->getFormBuilder()->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formPrepare) {
                    $formPrepare($event);
                }
            );
            
            /*
            $event->getFormBuilder()->add(
                'mygroup',
                'choice',
                [
                    'choices' => [
                        'core'         => 'mautic.lead.field.group.core',
                        'social'       => 'mautic.lead.field.group.social',
                        'personal'     => 'mautic.lead.field.group.personal',
                        'professional' => 'mautic.lead.field.group.professional',
                    ],
                    'attr' => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.lead.field.form.group.help',
                    ],
                    'expanded'    => false,
                    'multiple'    => false,
                    'label'       => 'mautic.lead.field.group',
                    'empty_value' => false,
                    'required'    => false,
                    'disabled'    => $disabled,
                ]
            );
            */
        }     
    }
}