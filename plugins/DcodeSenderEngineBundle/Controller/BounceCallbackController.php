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
use Mautic\LeadBundle\Entity\DoNotContact;

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
                        $addBouncePoints = intval($featureSettings["bounce{$status}_value"]);
                        $newBouncePoints = $prevBouncePoints+$addBouncePoints;
                        $bouncePointThreshold = intval($featureSettings["bounce_threshold"]);
                        
                        if ($addBouncePoints > 0){
                            $lead->addUpdatedField($integration::$bouncePointsFieldName, $newBouncePoints, $prevBouncePoints);
                        }

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
                        $manipulationLog->setProperties([
                            'status' => $status,
                            'error_message' => $errorMessage,
                            'bounce_points' => intval($featureSettings["bounce{$status}_value"])
                        ]);

                        $lead->addEventLog($manipulationLog);
                        $leadModel->saveEntity($lead);
                        
                        $bounceProcessor = $this->get('mauticplugin.dcodesenderengine.bounceprocessor');
                        $bounceProcessor->updateStat($stat, $status, $errorMessage);

                        if ($bouncePointThreshold > 0 && $newBouncePoints >= $bouncePointThreshold) {
                            if ($featureSettings["bounce_unsubscribe"]){
                                $emailModel->setDoNotContact($stat, $translator->trans('mautic.plugin.bounce_callback.status.bounce_threshold_reached', [
                                    '%threshold%' => $bouncePointThreshold,                                
                                    '%error_message%' => $errorMessage,                                
                                ]), DoNotContact::BOUNCED);

                                $manipulationLog = new LeadEventLog();
                                $manipulationLog->setLead($lead);

                                $manipulationLog->setAction('lead_unsubscribed');
                                $manipulationLog->setProperties([
                                    'threashold' => $status,                                    
                                    'bounce_points' => $addBouncePoints
                                ]);

                                $lead->addEventLog($manipulationLog);
                                $leadModel->saveEntity($lead);

                            }else{
                                $emailModel->setDoNotContact($stat, $translator->trans('mautic.plugin.bounce_callback.status.bounce_threshold_reached', [
                                    '%threshold%' => $bouncePointThreshold,                                
                                    '%error_message%' => $errorMessage,                                
                                ]), DoNotContact::BOUNCED);

                                $manipulationLog = new LeadEventLog();
                                $manipulationLog->setLead($lead);

                                $manipulationLog->setAction('lead_dnc');
                                $manipulationLog->setProperties([
                                    '%threshold%' => $bouncePointThreshold,                                
                                    '%error_message%' => $errorMessage,   
                                ]);

                                $lead->addEventLog($manipulationLog);
                                $leadModel->saveEntity($lead);
                            }  
                        }                
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
