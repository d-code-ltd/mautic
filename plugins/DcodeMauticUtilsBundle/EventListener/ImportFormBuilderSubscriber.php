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
                    if (isset($child->getConfig()->getOption('choices')['mautic.lead.special_fields'])){
                        $options = $child->getConfig()->getOptions();            // get the options
                        
                        $choices = $options['choices'];
                        foreach ($choices as $groupKey => $group){
                            foreach ($group as $choiceKey => $value){
                                $choices[$groupKey][$choiceKey] = $options['choice_label']($choiceKey,$value);
                            }
                        }
                        $choices['mautic.lead.special_fields']['_tags'] = 'tag';
                        $type = $child->getConfig()->getType()->getName();       // get the name of the type
                        $options['choices'] = $choices;

                        unset($options['choice_list']);
                        unset($options['choice_label']);
                        $form->add($key, $type, $options); // replace the field.                        
                    }

                }            
            };     

            $event->getFormBuilder()->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formPrepare) {
                    $formPrepare($event);
                }
            );                
        }     
    }
}