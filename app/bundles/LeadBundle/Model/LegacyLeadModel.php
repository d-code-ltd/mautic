<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class LegacyLeadModel.
 *
 * @deprecated 2.14.0 to be removed in 3.0; Used temporarily to get around circular depdenency for LeadModel
 */
class LegacyLeadModel
{
    /**
     * @var Container
     */
    private $container;

    /**
     * LegacyContactMerger constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

#    /**
#     * @param Lead $lead
#     * @param Lead $lead2
#     * @param bool $autoMode
#     *
#     * @return Lead
#     */
#    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true, $dispatch = true)
#    {
#        $leadId = $lead->getId();
#
#        if ($autoMode) {
#            //which lead is the oldest?
#            $winner = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
#            $loser  = ($winner->getId() === $leadId) ? $lead2 : $lead;
#        } else {
#            $winner = $lead2;
#            $loser  = $lead;
#        }
#
#        try {
#            /** @var ContactMerger $contactMerger */
#            $contactMerger = $this->container->get('mautic.lead.merger');
#
#            return $contactMerger->merge($winner, $loser);
#        } catch (SameContactException $exception) {
#            return $lead;
#        }
#    }

    /**
     * Merge two leads; if a conflict of data occurs, the newest lead will get precedence.
     *
     * @param Lead $lead
     * @param Lead $lead2
     * @param bool $autoMode If true, the newest lead will be merged into the oldes then deleted; otherwise, $lead will be merged into $lead2 then deleted
     *
     * @return Lead
     */
    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true, $dispatch = true)
    {
        //commenting logger rows, since LegacyLeadModel doesn't have a logger object.
        //
        //$this->logger->debug('LEAD: Merging leads');

        $leadId  = $lead->getId();
        $lead2Id = $lead2->getId();

        //if they are the same lead, then just return one
        if ($leadId === $lead2Id) {
            //$this->logger->debug('LEAD: Leads are the same');

            return $lead;
        }

        if ($autoMode) {
            //which lead is the oldest?
            $mergeWith = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
            $mergeFrom = ($mergeWith->getId() === $leadId) ? $lead2 : $lead;
        } else {
            $mergeWith = $lead2;
            $mergeFrom = $lead;
        }
        //$this->logger->debug('LEAD: Lead ID# '.$mergeFrom->getId().' will be merged into ID# '.$mergeWith->getId());

        //dispatch pre merge event
        $event = new LeadMergeEvent($mergeWith, $mergeFrom);
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_PRE_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);
        }

        //merge IP addresses
        $ipAddresses = $mergeFrom->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $mergeWith->addIpAddress($ip);

            //$this->logger->debug('LEAD: Associating with IP '.$ip->getIpAddress());
        }

        //merge fields
        $mergeFromFields = $mergeFrom->getFields();
        foreach ($mergeFromFields as $group => $groupFields) {
            foreach ($groupFields as $alias => $details) {
                if ('points' === $alias) {
                    // We have to ignore this as it's a special field and it will reset the points for the contact
                    continue;
                }

                //overwrite old lead's data with new lead's if new lead's is not empty
                if (!empty($details['value'])) {
                    $mergeWith->addUpdatedField($alias, $details['value']);

                    //$this->logger->debug('LEAD: Updated '.$alias.' = '.$details['value']);
                }
            }
        }

        //merge owner
        $oldOwner = $mergeWith->getOwner();
        $newOwner = $mergeFrom->getOwner();

        if ($oldOwner === null && $newOwner !== null) {
            $mergeWith->setOwner($newOwner);

            //$this->logger->debug('LEAD: New owner is '.$newOwner->getId());
        }

        // Sum points
        $mergeFromPoints = $mergeFrom->getPoints();
        $mergeWithPoints = $mergeWith->getPoints();
        $mergeWith->adjustPoints($mergeFromPoints);
        //$this->logger->debug('LEAD: Adding '.$mergeFromPoints.' points from lead ID #'.$mergeFrom->getId().' to lead ID #'.$mergeWith->getId().' with '.$mergeWithPoints.' points');

        //merge tags
        $mergeFromTags = $mergeFrom->getTags();
        $addTags       = $mergeFromTags->getKeys();
        $this->modifyTags($mergeWith, $addTags, null, false);

        //save the updated lead
        $this->saveEntity($mergeWith, false, $dispatch);

        // Update merge records for the lead about to be deleted
        $this->getMergeRecordRepository()->moveMergeRecord($mergeFrom->getId(), $mergeWith->getId());

        // Create an entry this contact was merged
        $mergeRecord = new MergeRecord();
        $mergeRecord->setContact($mergeWith)
            ->setDateAdded()
            ->setName($mergeFrom->getPrimaryIdentifier())
            ->setMergedId($mergeFrom->getId());
        $this->getMergeRecordRepository()->saveEntity($mergeRecord);

        //post merge events
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_POST_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_POST_MERGE, $event);
        }

        //delete the old
        $this->deleteEntity($mergeFrom);

        //return the merged lead
        return $mergeWith;
    }

}
