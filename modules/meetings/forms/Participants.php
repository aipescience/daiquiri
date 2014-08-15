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

class Meetings_Form_Participants extends Daiquiri_Form_Abstract {

    private $_submit;
    private $_entry;
    private $_meeting;
    private $_status;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function setMeeting($meeting) {
        $this->_meeting = $meeting;
    }

    public function setStatus($status) {
        $this->_status = $status;
    }

    public function init() {
        $this->addCsrfElement();
        
        // firstname, lastname and affiliation fields
        foreach (array('firstname','lastname','affiliation') as $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst($key),
                'class' => 'input-xxlarge',
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        // email fiels
        if (empty($this->_entry)) {
            $field = new Meetings_Form_Element_Email('email', array(
                'class' => 'input-xxlarge',
                'required' => true,
                'unique' => true,
                'meetingId' => $this->_meeting['id']
            ));
        } else {
            $field = new Meetings_Form_Element_Email('email', array(
                'class' => 'input-xxlarge',
                'required' => true,
                'meetingId' => $this->_meeting['id']
            ));
        }
        $this->addElement($field);

        // participant details
        $participantDetailKeysElements = array();
        foreach ($this->_meeting['participant_detail_keys'] as $id => $detailKey) {

            $options = array();
            foreach (explode(',',$detailKey['options']) as $option) {
                $options[] = ucfirst($option);
            }

            switch (Meetings_Model_ParticipantDetailKeys::$types[$detailKey['type_id']]) {
                case "checkbox":
                    $this->addElement('multiCheckbox', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => $options
                    ));
                    break;
                case "radio":
                    $this->addElement('radio', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => $options
                    ));
                    break;
                case "select":
                    $this->addElement('select', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => $options
                    ));
                    break;
                case "multiselect":
                    $this->addElement('multiselect', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => $options
                    ));
                    break;
                default:
                    $this->addElement('text', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ',$detailKey['key'])),
                        'required' => true,
                        'filters' => array('StringTrim'),
                        'validators' => array(
                            array('validator' => new Daiquiri_Form_Validator_Text()),
                        )
                    ));
            }

            $participantDetailKeysElements[] = $detailKey['key'];
        }

        // status
        if (!empty($this->_status)) {
            $this->addElement('select', 'status_id', array(
                'label' => 'Status',
                'required' => true,
                'multiOptions' => $this->_status
            ));
        }

        // arrival and departure
        $this->addElement('text', 'arrival', array(
            'label' => 'Arrival',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'departure', array(
            'label' => 'Departure',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));

        // contributions
        $contributionElements = array();
        foreach ($this->_meeting['contribution_types'] as $id => $contribution_type) {
            $this->addElement('checkbox', $contribution_type . '_bool', array(
                'label' => ucfirst($contribution_type),
            ));
            $this->addElement('text', $contribution_type . '_title', array(
                'label' => 'Title',
                'class' => 'input-xxlarge',
                'required' => false,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $this->addElement('textarea', $contribution_type . '_abstract', array(
                'label' => 'Abstract',
                'class' => 'input-xxlarge',
                'rows' => 6,
                'required' => false,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Textarea()),
                )
            ));
            $contributionElements[] = $contribution_type . '_bool';
            $contributionElements[] = $contribution_type . '_title';
            $contributionElements[] = $contribution_type . '_abstract';
        }

        // captcha and submit buttons
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $participantDetailKeysElements),'personal', 'Personal data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        if (!empty($contributionElements)) {
            $this->addHorizontalGroup($contributionElements,'contributions', 'Contributions');
        }
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('firstname','lastname','affiliation','email','status_id','arrival','departure') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        foreach ($this->_meeting['participant_detail_keys'] as $key_id => $detailKey) {
            if (Meetings_Model_ParticipantDetailKeys::$types[$detailKey['type_id']] === 'default') {
                $this->setDefault($detailKey['key'], $this->_entry[$detailKey['key']]);
            } else {
                $options = explode(',',$detailKey['options']);
                $option_id = array_search($this->_entry[$detailKey['key']],$options);
                $this->setDefault($detailKey['key'], $option_id);
            }
        }

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
        if (isset($this->_entry['contributions'])) {
            foreach ($this->_entry['contributions'] as $contribution) {
                $this->setDefault($contribution['contribution_type'] . '_bool', 1);
                $this->setDefault($contribution['contribution_type'] . '_title', $contribution['title']);
                $this->setDefault($contribution['contribution_type'] . '_abstract', $contribution['abstract']);
            }
        }
    }

}
