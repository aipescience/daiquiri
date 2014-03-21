<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
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
        $this->setFormDecorators();
        $this->addCsrfElement();
        
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

        // add elements
        foreach ($this->_meeting['participant_detail_keys'] as $id => $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst($key),
                'class' => 'input-xxlarge',
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $elements[] = $key;
        }

        if (!empty($this->_status)) {
            $this->addElement('select', 'status_id', array(
                'label' => 'Status',
                'required' => true,
                'multiOptions' => $this->_status
            ));
        }

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

        $elements = array();
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
            $elements[] = $contribution_type . '_bool';
            $elements[] = $contribution_type . '_title';
            $elements[] = $contribution_type . '_abstract';
        }

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $this->_meeting['participant_detail_keys']),'personal', 'Personal data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        $this->addHorizontalGroup($elements,'contributions', 'Contributions');

        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array_merge(array('firstname','lastname','affiliation','email','status_id','arrival','departure'),$this->_meeting['participant_detail_keys']) as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
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
