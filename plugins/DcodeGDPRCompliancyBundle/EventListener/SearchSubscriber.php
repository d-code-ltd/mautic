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

use Mautic\PluginBundle\Entity\Integration;

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

        var_dump($string);

        $expr  = $q->expr()->andX(sprintf('%s = :%s', 'email_hash', $alias));

        $query=$q->getQuery();
        $query->getSQL();

        var_dump($query);
        var_dump($query->getResult()->getSql());

        $event->setReturnParameters(true); // replace search string
        $event->setStrict(true);           // don't use like
        $event->setSearchStatus(true);     // finish searching
    }
























    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildEmailPendingQuery(LeadBuildSearchEvent $event)
    {
        $q       = $event->getQueryBuilder();
        $emailId = (int) $event->getString();
        /** @var Email $email */
        $email = $this->emailRepository->getEntity($emailId);
        if (null !== $email) {
            $variantIds = $email->getRelatedEntityIds();
            $nq         = $this->emailRepository->getEmailPendingQuery($emailId, $variantIds);
            if (!$nq instanceof QueryBuilder) {
                return;
            }

            $nq->select('l.id'); // select only id
            $nsql = $nq->getSQL();
            foreach ($nq->getParameters() as $pk => $pv) { // replace all parameters
                $nsql = preg_replace('/:'.$pk.'/', is_bool($pv) ? (int) $pv : $pv, $nsql);
            }
            $query = $q->expr()->in('l.id', sprintf('(%s)', $nsql));
            $event->setSubQuery($query);

            return;
        }

        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'message_queue',
                'alias'      => 'mq',
                'condition'  => 'l.id = mq.lead_id',
            ],
        ];

        $config = [
            'column' => 'mq.channel_id',
            'params' => [
                'mq.channel' => 'email',
                'mq.status'  => MessageQueue::STATUS_PENDING,
            ],
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildPageHitSourceQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'page_hits',
                'alias'      => 'ph',
                'condition'  => 'l.id = ph.lead_id',
            ],
        ];

        $config = [
            'column' => 'ph.source',
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildPageHitSourceIdQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'page_hits',
                'alias'      => 'ph',
                'condition'  => 'l.id = ph.lead_id',
            ],
        ];

        $config = [
            'column' => 'ph.source_id',
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildPageHitIdQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'page_hits',
                'alias'      => 'ph',
                'condition'  => 'l.id = ph.lead_id',
            ],
        ];

        $config = [
            'column' => 'ph.redirect_id',
        ];
        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildEmailQueuedQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'message_queue',
                'alias'      => 'mq',
                'condition'  => 'l.id = mq.lead_id',
            ],
        ];

        $config = [
            'column' => 'mq.channel_id',
            'params' => [
                'mq.channel' => 'email',
                'mq.status'  => MessageQueue::STATUS_SENT,
            ],
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildEmailSentQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'email_stats',
                'alias'      => 'es',
                'condition'  => 'l.id = es.lead_id',
            ],
        ];

        $config = [
            'column' => 'es.email_id',
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildEmailReadQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'email_stats',
                'alias'      => 'es',
                'condition'  => 'l.id = es.lead_id',
            ],
        ];

        $config = [
            'column' => 'es.email_id',
            'params' => [
                'es.is_read' => 1,
            ],
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildSmsSentQuery(LeadBuildSearchEvent $event)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'sms_message_stats',
                'alias'      => 'ss',
                'condition'  => 'l.id = ss.lead_id',
            ],
        ];

        $config = [
            'column' => 'ss.sms_id',
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildWebSentQuery(LeadBuildSearchEvent $event)
    {
        $this->buildNotificationSentQuery($event);
    }

    /**
     * @param LeadBuildSearchEvent $event
     */
    private function buildMobileSentQuery(LeadBuildSearchEvent $event)
    {
        $this->buildNotificationSentQuery($event, true);
    }

    /**
     * @param LeadBuildSearchEvent $event
     * @param bool                 $isMobile
     */
    private function buildNotificationSentQuery(LeadBuildSearchEvent $event, $isMobile = false)
    {
        $tables = [
            [
                'from_alias' => 'l',
                'table'      => 'push_notification_stats',
                'alias'      => 'ns',
                'condition'  => 'l.id = ns.lead_id',
            ],
            [
                'from_alias' => 'ns',
                'table'      => 'push_notifications',
                'alias'      => 'pn',
                'condition'  => 'pn.id = ns.notification_id',
            ],
        ];

        $config = [
            'column' => 'pn.id',
            'params' => [
                'pn.mobile' => (int) $isMobile,
            ],
        ];

        $this->buildJoinQuery($event, $tables, $config);
    }

    /**
     * @param LeadBuildSearchEvent $event
     * @param array                $tables
     * @param array                $config
     */
    private function buildJoinQuery(LeadBuildSearchEvent $event, array $tables, array $config)
    {
        if (!isset($config['column']) || 0 === count($tables)) {
            return;
        }

        $alias = $event->getAlias();
        $q     = $event->getQueryBuilder();
        $expr  = $q->expr()->andX(sprintf('%s = :%s', $config['column'], $alias));

        if (isset($config['params'])) {
            $params = (array) $config['params'];
            foreach ($params as $name => $value) {
                $param = $q->createNamedParameter($value);
                $expr->add(sprintf('%s = %s', $name, $param));
            }
        }

        $this->leadRepo->applySearchQueryRelationship($q, $tables, true, $expr);

        $event->setReturnParameters(true); // replace search string
        $event->setStrict(true);           // don't use like
        $event->setSearchStatus(true);     // finish searching
    }
}
