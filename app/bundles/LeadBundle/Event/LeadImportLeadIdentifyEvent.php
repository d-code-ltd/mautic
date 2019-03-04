<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class LeadEvent.
 */
class LeadImportLeadIdentifyEvent extends CommonEvent
{
    /**
     * @param Lead $lead
     * @param bool $isNew
     */
    public function __construct(&$lead, &$fieldData, &$fields, &$data)
    {
        $this->entity = &$lead;        
        $this->fieldData = &$fieldData;        
        $this->fields = &$fields;        
        $this->data = &$data;        
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Sets the Lead entity.
     *
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->entity = $lead;
    }

    /**
     * Sets the Lead entity.
     *
     * @param Lead $lead
     */
    public function clearLead()
    {
        $this->entity = null;
    }


    public function getFieldData(){
        return $this->fieldData;
    }

    public function setFieldData($fieldData){
        $this->fieldData = $fieldData;
    }

    public function getFields(){
        return $this->fields;
    }

    public function setFields($fields){
        $this->fields = $fields;
    }

    public function getData(){
        return $this->data;
    }

    public function setData($data){
        $this->data = $data;
    }

}
