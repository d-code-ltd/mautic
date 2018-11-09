<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BounceCallbackController extends CommonController
{
    public function callbackAction(Request $request)
    {
        //$this->integrationHelper = $this->get('mautic.helper.integration');         
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('bounce_callback', $supportedFeatures)) {
            return;
        }

        //TODO: decide whether to accept json array or single call !!!

        $translator = $this->get('translator');


        $status = $this->request->get('status', 0);

        if (empty($status)){
            $emailModel = $this->getModel('email');
            $leadModel = $this->getModel('lead');
            $stat  = $emailModel->getEmailStatus($idHash);

            if (!empty($stat)) {
                $email = $stat->getEmail();
                $lead = $stat->getLead();
                $leadModel->setCurrentLead($lead);


                /* TODO: 
                        $emailModel::processMailerCallback 
                */

                //TODO if user reached threashold => DoNotContact / Unsubscribe

                //TODO Log bounce event
                
            }else{
                $message = $translator->trans('mautic.email.stat_record.not_found');
            }
        }else{
            $message = $translator->trans('mautic.plugin.bounce_callback.status.notfound');
        }

/*
        if (!empty($stat)) {
            if ($email = $stat->getEmail()) {
                $template = $email->getTemplate();
                if ('mautic_code_mode' === $template) {
                    // Use system default
                    $template = null;
                }

                
                $unsubscribeForm = $email->getUnsubscribeForm();
                if ($unsubscribeForm != null && $unsubscribeForm->isPublished()) {
                    $formTemplate = $unsubscribeForm->getTemplate();
                    $formModel    = $this->getModel('form');
                    $formContent  = '<div class="mautic-unsubscribeform">'.$formModel->getContent($unsubscribeForm).'</div>';
                }
            }
        }

        if (empty($template) && empty($formTemplate)) {
            $template = $this->coreParametersHelper->getParameter('theme');
        } elseif (!empty($formTemplate)) {
            $template = $formTemplate;
        }

        $theme = $this->factory->getTheme($template);
        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }
        $contentTemplate = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':message.html.php');

        if (!empty($stat)) {
            $lead = $stat->getLead();
            if ($lead) {
                // Set the lead as current lead
                $leadModel->setCurrentLead($lead);
            }
            // Set lead lang
            if ($lead->getPreferredLocale()) {
                $translator->setLocale($lead->getPreferredLocale());
            }

            if (!$this->get('mautic.helper.core_parameters')->getParameter('show_contact_preferences')) {
                $message = $this->getUnsubscribeMessage($idHash, $model, $stat, $translator);
            } elseif ($lead) {
                $action = $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]);

                $viewParameters = [
                    'lead'                         => $lead,
                    'idHash'                       => $idHash,
                    'showContactFrequency'         => $this->get('mautic.helper.core_parameters')->getParameter('show_contact_frequency'),
                    'showContactPauseDates'        => $this->get('mautic.helper.core_parameters')->getParameter('show_contact_pause_dates'),
                    'showContactPreferredChannels' => $this->get('mautic.helper.core_parameters')->getParameter('show_contact_preferred_channels'),
                    'showContactCategories'        => $this->get('mautic.helper.core_parameters')->getParameter('show_contact_categories'),
                    'showContactSegments'          => $this->get('mautic.helper.core_parameters')->getParameter('show_contact_segments'),
                ];

                $form = $this->getFrequencyRuleForm($lead, $viewParameters, $data, true, $action);
                if (true === $form) {
                    return $this->postActionRedirect(
                        [
                            'returnUrl'       => $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $contentTemplate,
                        ]
                    );
                }

                $formView = $form->createView();
               
                if ($email && ($prefCenter = $email->getPreferenceCenter()) && ($prefCenter->getIsPreferenceCenter())) {
                    $html = $prefCenter->getCustomHtml();
                    // check if tokens are present
                    $savePrefsPresent = false !== strpos($html, 'data-slot="saveprefsbutton"') ||
                                        false !== strpos($html, BuilderSubscriber::saveprefsRegex);
                    if ($savePrefsPresent) {
                        // set custom tag to inject end form
                        // update show pref center slots by looking for their presence in the html
                        $params = array_merge(
                            $viewParameters,
                            [
                                'form'                         => $formView,
                                'custom_tag'                   => '<a name="end-'.$formView->vars['id'].'"></a>',
                                'showContactFrequency'         => false !== strpos($html, 'data-slot="channelfrequency"') || false !== strpos($html, BuilderSubscriber::channelfrequency),
                                'showContactSegments'          => false !== strpos($html, 'data-slot="segmentlist"') || false !== strpos($html, BuilderSubscriber::segmentListRegex),
                                'showContactCategories'        => false !== strpos($html, 'data-slot="categorylist"') || false !== strpos($html, BuilderSubscriber::categoryListRegex),
                                'showContactPreferredChannels' => false !== strpos($html, 'data-slot="preferredchannel"') || false !== strpos($html, BuilderSubscriber::preferredchannel),
                            ]
                        );
                        // Replace tokens in preference center page
                        $event = new PageDisplayEvent($html, $prefCenter, $params);
                        $this->get('event_dispatcher')
                             ->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
                        $html = $event->getContent();
                        $html = preg_replace('/'.BuilderSubscriber::identifierToken.'/', $lead->getPrimaryIdentifier(), $html);
                    } else {
                        unset($html);
                    }
                }

                if (empty($html)) {
                    $html = $this->get('mautic.helper.templating')->getTemplating()->render(
                        'MauticEmailBundle:Lead:preference_options.html.php',
                        array_merge(
                            $viewParameters,
                            [
                                'form'         => $formView,
                                'currentRoute' => $this->generateUrl(
                                    'mautic_contact_action',
                                    [
                                        'objectAction' => 'contactFrequency',
                                        'objectId'     => $lead->getId(),
                                    ]
                                ),
                            ]
                        )
                    );
                }
                $message = $html;
            }
        } else {
            $message = $translator->trans('mautic.email.stat_record.not_found');
        }
 */







/*


        
        $em          = $this->get('doctrine.orm.entity_manager');
        $contactRepo = $em->getRepository(Lead::class);
        $matchData   = [
            'email' => $requestBody['email'],
        ];


        $contact = $contactRepo->findOneBy($matchData);

        if ($contact === null) {
            $contact = new Lead();
            $contact->setEmail($requestBody['email']);
            $contact->setLastActive(new \DateTime());
        }

        $pushIdCreated = false;

        if (array_key_exists('push_id', $requestBody)) {
            $pushIdCreated = true;
            $contact->addPushIDEntry($requestBody['push_id'], $requestBody['enabled'], true);
            $contactRepo->saveEntity($contact);
        }

        $statCreated = false;

        if (array_key_exists('stat', $requestBody)) {
            $stat             = $requestBody['stat'];
            $notificationRepo = $em->getRepository(Notification::class);
            $notification     = $notificationRepo->getEntity($stat['notification_id']);

            if ($notification !== null) {
                $statCreated = true;
                $this->getModel('notification')->createStatEntry($notification, $contact, $stat['source'], $stat['source_id']);
            }
        }
*/

        return new JsonResponse([
            'status'  => $status,
            'leadId'  => $leadId,
            'emailId' => $emailId
        ]);
    }
}
