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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;

use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class SearchSubscriber.
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var LeadRepository
     */
    private $leadRepo;

    /**
     * @var EmailRepository
     */
    private $emailRepository;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * SearchSubscriber constructor.
     *
     * @param LeadModel     $leadModel
     * @param EntityManager $entityManager
     */
    public function __construct(LeadModel $leadModel, EntityManager $entityManager, IntegrationHelper $integrationHelper)
    {
        $this->leadModel       = $leadModel;
        $this->leadRepo        = $leadModel->getRepository();
        $this->emailRepository = $entityManager->getRepository(Email::class);
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [            
            CoreEvents::BUILD_COMMAND_LIST         => ['onBuildCommandList', 0],
            LeadEvents::LEAD_BUILD_SEARCH_COMMANDS => ['onBuildSearchCommands', 0],
        ];
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        if (!$integration){
            return;
        }
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        if ($this->security->isGranted(['lead:leads:viewown', 'lead:leads:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.lead.leads',
                ['mautic.plugin.gdprcompliancy.lead.searchcommand.emailhash']
            );            
        }
    }

    /**
     * @param LeadBuildSearchEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function onBuildSearchCommands(LeadBuildSearchEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');
        if (!$integration){
            return;
        }
        $integrationSettings = $integration->getIntegrationSettings();
        if (!$integration || $integrationSettings->getIsPublished() === false) {
            return;
        }

        switch ($event->getCommand()) {
            case $this->translator->trans('mautic.plugin.gdprcompliancy.lead.searchcommand.emailhash'):
            case $this->translator->trans('mautic.plugin.gdprcompliancy.lead.searchcommand.emailhash', [], null, 'en_US'):
                    $this->buildEmailHashQuery($event);
                break;            
        }
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildEmailHashQuery(LeadBuildSearchEvent $event)
    {
        $q       = $event->getQueryBuilder();
        $string  = $event->getString();
        $alias = $event->getAlias();
        $integration = $this->integrationHelper->getIntegrationObject('GDPRCompliancy');        
        $integrationSettings = $integration->getIntegrationSettings();
        $featureSettings = $integrationSettings->getFeatureSettings();
        
        $expr  = $q->expr()->andX(sprintf("%s = '%s'", 'email_hash', $integration->hashValue($string, $featureSettings['hash_salt'])));
        $event->setSubQuery($expr);

        //$event->setReturnParameters(true); // replace search string
        //$event->setStrict(true);           // don't use like
        $event->setSearchStatus(true);     // finish searching
    }

}
