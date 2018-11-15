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
        $this->integrationHelper = $this->get('mautic.helper.integration');         
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('bounce_callback', $supportedFeatures)) {
            return;
        }

        $translator = $this->get('translator');

        $idHash = $this->request->get('idHash', '');
        $status = $this->request->get('status', 0);
        $error_message = $this->request->get('error_message', '');
        
        if (!empty($status)){
            $emailModel = $this->getModel('email');
            $leadModel = $this->getModel('lead');
            $stat  = $emailModel->getEmailStatus($idHash);

            var_dump($stat);

            if (!empty($stat)) {
                if (!$stat->isFailed()){
                    $email = $stat->getEmail();
                    $lead = $stat->getLead();
                    $leadModel->setCurrentLead($lead);

                    var_dump($email, $lead);

                    /* TODO: 
                            $emailModel::processMailerCallback 
                    */

                    //TODO if user reached threashold => DoNotContact / Unsubscribe

                    //TODO Log bounce event
                }else{
                    $message = $translator->trans('mautic.plugin.bounce_callback.status.already_reported');    
                }
            }else{
                $message = $translator->trans('mautic.email.stat_record.not_found');
            }
        }else{
            $message = $translator->trans('mautic.plugin.bounce_callback.status.notfound');
        }


/*
    public function processMailerCallback(array $response)
    {
        if (empty($response)) {
            return;
        }

        $statRepo = $this->getStatRepository();
        $alias    = $statRepo->getTableAlias();
        if (!empty($alias)) {
            $alias .= '.';
        }

        // Keep track to prevent duplicates before flushing
        $emails = [];
        $dnc    = [];

        foreach ($response as $type => $entries) {
            if (!empty($entries['hashIds'])) {
                $stats = $statRepo->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => $alias.'trackingHash',
                                    'expr'   => 'in',
                                    'value'  => array_keys($entries['hashIds']),
                                ],
                            ],
                        ],
                    ]
                );

                
                foreach ($stats as $s) {
                    $reason = $entries['hashIds'][$s->getTrackingHash()];
                    if ($this->translator->hasId('mautic.email.bounce.reason.'.$reason)) {
                        $reason = $this->translator->trans('mautic.email.bounce.reason.'.$reason);
                    }

                    $dnc[] = $this->setDoNotContact($s, $reason, $type);

                    $s->setIsFailed(true);
                    $this->em->persist($s);
                }
            }

            if (!empty($entries['emails'])) {
                foreach ($entries['emails'] as $email => $reason) {
                    if (in_array($email, $emails)) {
                        continue;
                    }
                    $emails[] = $email;

                    $leadId = null;
                    if (is_array($reason)) {
                        // Includes a lead ID
                        $leadId = $reason['leadId'];
                        $reason = $reason['reason'];
                    }

                    if ($this->translator->hasId('mautic.email.bounce.reason.'.$reason)) {
                        $reason = $this->translator->trans('mautic.email.bounce.reason.'.$reason);
                    }

                    $dnc = array_merge($dnc, $this->setEmailDoNotContact($email, $type, $reason, true, $leadId));
                }
            }
        }

        return $dnc;
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

        if (!empty($message)){
            return new JsonResponse([
                'success' => false,
                'message'  => $message,                
            ]);
        }else{
            return new JsonResponse([
                'success' => true,
                'status'  => $status,
                'leadId'  => $leadId,
                'emailId' => $emailId
            ]);
        }
    }
}
