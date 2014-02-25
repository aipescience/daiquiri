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

class Meetings_Form_Registration extends Daiquiri_Form_Abstract {

    private $_submit;
    private $_entry;
    private $_meeting;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function setMeeting($meeting) {
        $this->_meeting = $meeting;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        foreach (array('firstname','lastname','affiliation') as $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst($key),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        if (empty($this->_entry)) {
            $field = new Meetings_Form_Element_Email('email', array(
                'required' => true,
                'unique' => true,
                'meetingId' => $this->_meeting['id']
            ));
        } else {
            $field = new Meetings_Form_Element_Email('email', array(
                'required' => true,
                'meetingId' => $this->_meeting['id']
            ));
        }
        $this->addElement($field);

        foreach ($this->_meeting['participant_detail_keys'] as $id => $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst(str_replace('_',' ',$key)),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $elements[] = $key;
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

        $contributionElements = array();
        foreach ($this->_meeting['contribution_types'] as $id => $contribution_type) {
            $this->addElement('checkbox', $contribution_type . '_bool', array(
                'class' => 'daiquiri-toggle-control-group',
                'label' => ucfirst($contribution_type),
            ));
            $this->addElement('text', $contribution_type . '_title', array(
                'label' => 'Title',
                'class' => 'input-xxlarge daiquiri-hide-control-group',
                'required' => false,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $this->addElement('textarea', $contribution_type . '_abstract', array(
                'label' => 'Abstract',
                'class' => 'input-xxlarge daiquiri-hide-control-group',
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

        $this->addElement(new Daiquiri_Form_Element_Captcha('captcha'));

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $this->_meeting['participant_detail_keys']),'personal', 'Personal data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        $this->addHorizontalGroup($contributionElements,'contributions', 'Contributions');

        $this->addCaptchaGroup('captcha');
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        $this->setDefault('arrival', $this->_meeting['begin']);
        $this->setDefault('departure', $this->_meeting['end']);
    }

}
