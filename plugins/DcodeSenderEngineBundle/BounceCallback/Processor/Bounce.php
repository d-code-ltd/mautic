<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DcodeSenderEngineBundle\BounceCallback\Processor;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\Swiftmailer\Transport\BounceProcessorInterface;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\EmailBundle\MonitoredEmail\Processor\ProcessorInterface;

use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\DoNotContact;

class Bounce implements ProcessorInterface
{  
    /**
     * @var StatRepository
     */
    protected $statRepository;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $bouncerAddress;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Message
     */
    protected $message;

    /**
     * Bounce constructor.
     *     
     * @param StatRepository      $statRepository
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     * @param LoggerInterface     $logger
     */
    public function __construct(        
        StatRepository $statRepository,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {            
        $this->statRepository = $statRepository;
        $this->leadModel      = $leadModel;
        $this->translator     = $translator;
        $this->logger         = $logger;
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function process($idHash, $stat, $status, $errorMessage, $lead, $leadModel, $email, $emailModel, $integration)
    {
        $integrationSettings = $integration->getIntegrationSettings();
        $featureSettings   = $integrationSettings->getFeatureSettings();        

        $this->logger->debug('SenderEngine BounceCallback: Processing idHash '.idHash.' for bounce. Status: '.$status);

        $prevBouncePoints = intval($lead->getFieldValue($integration::$bouncePointsFieldName));
        $addBouncePoints = intval($featureSettings["bounce{$status}_value"]);
        $newBouncePoints = $prevBouncePoints+$addBouncePoints;
        $bouncePointThreshold = intval($featureSettings["bounce_threshold"]);

        if ($addBouncePoints > 0){
            $lead->addUpdatedField($integration::$bouncePointsFieldName, $newBouncePoints, $prevBouncePoints);
            $this->logger->debug('SenderEngine BounceCallback: added '.$addBouncePoints.' bounce poiints to contact');
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
        
        
        $bounceProcessor->updateStat($stat, $status, $errorMessage);

        if ($bouncePointThreshold > 0 && $newBouncePoints >= $bouncePointThreshold) {                            
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


            $this->logger->debug('SenderEngine BounceCallback: put lead on DNC');
        }       

        return true;
    }

    /**
     * @param Stat         $stat
     * @param int $status
     */
    //protected
    public function updateStat(Stat $stat, $status, $errorMessage)
    {
        $dtHelper    = new DateTimeHelper();
        $openDetails = $stat->getOpenDetails();

        if (!isset($openDetails['bounces'])) {
            $openDetails['bounces'] = [];
        }

        $openDetails['bounces'][] = [
            'datetime' => $dtHelper->toUtcString(),
            'reason'   => $errorMessage,
            'code'     => $status,
            'type'     => '',
        ];

        $stat->setOpenDetails($openDetails);        
        $stat->setIsFailed(true);

        $this->statRepository->saveEntity($stat);
    }
}
