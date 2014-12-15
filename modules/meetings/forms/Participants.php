<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Meetings_Form_Participants extends Meetings_Form_Abstract {

    /**
     * The different status entries to choose from.
     * @var array
     */
    private $_status;

    /**
     * Sets $_status.
     * @param array $status the different status entries to choose from.
     */
    public function setStatus($status) {
        $this->_status = $status;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
        
        // firstname, lastname and affiliation fields
        foreach (array('firstname','lastname','affiliation') as $key) {
            $this->addTextElement($key, array(
                'label' => ucfirst($key),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        // email fiels
        if (empty($this->_entry)) {
            $field = new Meetings_Form_Element_Email(array(
                'meetingId' => $this->_meeting['id']
            ));
        } else {
            $field = new Meetings_Form_Element_Email(array(
                'meetingId' => $this->_meeting['id'],
                'excludeId' => $this->_entry['id']
            ));
        }
        $this->addElement($field);

        // participant details
        $participantDetailKeysElements = array();
        foreach ($this->_meeting['participant_detail_keys'] as $detailKey) {
            $participantDetailKeysElements[] = $this->addParticipantDetailElement($detailKey,'span5');
        }

        // status
        if (!empty($this->_status)) {
            $this->addSelectElement('status_id', array(
                'label' => 'Status',
                'required' => true,
                'multiOptions' => $this->_status
            ));
        }

        // arrival and departure
        $this->addTextElement('arrival', array(
            'label' => 'Arrival',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('departure', array(
            'label' => 'Departure',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));

        // contributions
        $contributionElements = array();
        foreach ($this->_meeting['contribution_types'] as $contribution_type) {
            $contributionElements[] = $this->addContributionElement($contribution_type);
        }

        // captcha and submit buttons
        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add display group for participant information
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $participantDetailKeysElements),'personal');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status');
        }

        // add display group for arrival and departure
        $this->addHorizontalGroup(array('arrival','departure'),'attendance');

        // add display groups for contributions
        if (!empty($contributionElements)) {
            foreach ($contributionElements as $key => $elements) {
                $this->addHorizontalGroup($elements,'contributions-' . $key);
            }
        }

        // add display group for submit buttons
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set participant information fields
        foreach (array('firstname','lastname','affiliation','email','status_id','arrival','departure') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }

        // set detail fields
        foreach ($this->_meeting['participant_detail_keys'] as $detailKey) {
            if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$detailKey['type_id']], array('checkbox','multiselect'))) {
                $value = Zend_Json::decode($this->_entry['details'][$detailKey['key']]);
            } else {
                $value = $this->_entry['details'][$detailKey['key']];
            }
            $this->setDefault($detailKey['key'],$value);
        }

        // set arrival and departure
        if (isset($this->_entry['arrival'])) {
            $this->setDefault('arrival', $this->_entry['arrival']);
        } else {
            $this->setDefault('arrival', $this->_meeting['begin']);
        }
        if (isset($this->_entry['departure'])) {
            $this->setDefault('departure', $this->_entry['departure']);
        } else {
            $this->setDefault('departure', $this->_meeting['end']);
        }

        // set contribution
        if (isset($this->_entry['contributions'])) {
            foreach ($this->_entry['contributions'] as $key => $contribution) {
                $this->setDefault($key . '_bool', 1);
                $this->setDefault($key . '_title', $contribution['title']);
                $this->setDefault($key . '_abstract', $contribution['abstract']);
            }
        }
    }

}
