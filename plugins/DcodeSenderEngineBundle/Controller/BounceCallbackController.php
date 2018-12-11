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
        if (!$integration){
            return;
        }
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
                    
                        $bounceProcessor = $this->get('mauticplugin.dcodesenderengine.bounceprocessor');
                        $bounceProcessor->process($idHash, $stat, $status, $errorMessage, $lead, $leadModel, $email, $emailModel, $integration);
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
