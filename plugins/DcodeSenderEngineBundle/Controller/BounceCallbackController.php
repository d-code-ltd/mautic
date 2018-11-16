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

use Mautic\LeadBundle\Entity\LeadEventLog;

class BounceCallbackController extends CommonController
{
    public function callbackAction(Request $request)
    {
        $this->integrationHelper = $this->get('mautic.helper.integration');         
        $integration = $this->integrationHelper->getIntegrationObject('SenderEngine');
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }
        
        $supportedFeatures = $integration->getSupportedFeatures();
        if (!in_array('bounce_callback', $supportedFeatures)) {
            return;
        }
        $featureSettings   = $integrationSettings->getFeatureSettings();


        $translator = $this->get('translator');

        $idHash = $this->request->get('idHash', '');
        $status = intval($this->request->get('status', 0));
        $errorMessage = $this->request->get('error_message', '');
        
        if (!empty($status)){
            $emailModel = $this->getModel('email');
            $leadModel = $this->getModel('lead');
            $stat  = $emailModel->getEmailStatus($idHash);            

            if (!empty($stat)) {
                if (!$stat->isFailed()){
                    if (isset($featureSettings["bounce{$status}_value"])){
                        $email = $stat->getEmail();
                        $lead = $stat->getLead();
                        $leadModel->setCurrentLead($lead);
                    
                        $prevBouncePoints = intval($lead->getFieldValue($integration::$bouncePointsFieldName));

                        var_dump($prevBouncePoints, $status, $featureSettings["bounce{$status}_value"]);
                        if (intval($featureSettings["bounce{$status}_value"]) > 0){
                            $lead->addUpdatedField($integration::$bouncePointsFieldName, $prevBouncePoints+intval($featureSettings["bounce{$status}_value"]), $prevBouncePoints);
                       
  
                            
                            
                            $manipulator = $lead->getManipulator();

                            $manipulationLog = new LeadEventLog();
                            $manipulationLog->setLead($lead);
                            
                            if (!empty($manipulator)){
                                $manipulationLog->setBundle($manipulator->getBundleName())
                                ->setObject($manipulator->getObjectName())
                                ->setObjectId($manipulator->getObjectId());
                            }else{
                                //Test whether bundle, object and objectId has any affect
                            }
                            
                            $manipulationLog->setAction('email_bounced');
                            
                            //$description = $manipulator->getObjectDescription();
                            //$manipulationLog->setProperties(['object_description' => $description]);

                            $lead->addEventLog($manipulationLog);
                            $leadModel->saveEntity($lead);
                        }


                        /* TODO: 
                                $emailModel::processMailerCallback 
                        */

                        //TODO if user reached threashold => DoNotContact / Unsubscribe

                        //TODO Log bounce event
                    }else{
                        $message = $translator->trans('mautic.plugin.bounce_callback.status.unhandled');    
                    }
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

    public function process(Message $message)
    {
        $this->message = $message;
        $bounce        = false;

        $this->logger->debug('MONITORED EMAIL: Processing message ID '.$this->message->id.' for a bounce');

        // Does the transport have special handling such as Amazon SNS?
        if ($this->transport instanceof BounceProcessorInterface) {
            try {
                $bounce = $this->transport->processBounce($this->message);
            } catch (BounceNotFound $exception) {
                // Attempt to parse a bounce the standard way
            }
        }

        if (!$bounce) {
            try {
                $bounce = (new Parser($this->message))->parse();
            } catch (BounceNotFound $exception) {
                return false;
            }
        }

        $searchResult = $this->contactFinder->find($bounce->getContactEmail(), $bounce->getBounceAddress());
        if (!$contacts = $searchResult->getContacts()) {
            // No contacts found so bail
            return false;
        }

        $stat    = $searchResult->getStat();
        $channel = 'email';
        if ($stat) {
            // Update stat entry
            $this->updateStat($stat, $bounce);

            if ($stat->getEmail() instanceof Email) {
                // We know the email ID so set it to append to the the DNC record
                $channel = ['email' => $stat->getEmail()->getId()];
            }
        }

        $comments = $this->translator->trans('mautic.email.bounce.reason.'.$bounce->getRuleCategory());
        foreach ($contacts as $contact) {
            $this->leadModel->addDncForLead($contact, $channel, $comments);
        }

        return true;
    }

    
    protected function updateStat(Stat $stat, BouncedEmail $bouncedEmail)
    {
        $dtHelper    = new DateTimeHelper();
        $openDetails = $stat->getOpenDetails();

        if (!isset($openDetails['bounces'])) {
            $openDetails['bounces'] = [];
        }

        $openDetails['bounces'][] = [
            'datetime' => $dtHelper->toUtcString(),
            'reason'   => $bouncedEmail->getRuleCategory(),
            'code'     => $bouncedEmail->getRuleNumber(),
            'type'     => $bouncedEmail->getType(),
        ];

        $stat->setOpenDetails($openDetails);

        $retryCount = $stat->getRetryCount();
        ++$retryCount;
        $stat->setRetryCount($retryCount);

        if ($fail = $bouncedEmail->isFinal() || $retryCount >= 5) {
            $stat->setIsFailed(true);
        }

        $this->statRepository->saveEntity($stat);
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
            ]);
        }
    }
}
