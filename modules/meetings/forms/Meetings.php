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

class Meetings_Form_Meetings extends Daiquiri_Form_Abstract {

    private $_submit;
    private $_entry;
    private $_contributionTypes = array();
    private $_participantDetailKeys = array();

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function setContributionTypes($contributionTypes) {
        foreach($contributionTypes as $key => $value) {
            $this->_contributionTypes[$key] = ucfirst($value);
        }
    }

    public function setParticipantDetailKeys($participantDetailKeys) {
        foreach($participantDetailKeys as $key => $value) {
            $this->_participantDetailKeys[$key] = ucfirst($value);
        }
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('text', 'title', array(
            'label' => 'Title of the Meeting',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('textarea', 'description', array(
            'label' => 'Description',
            'rows' => 6,
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));
        $this->addElement('multiCheckbox', 'contribution_type_id', array(
            'label' => 'Contribution types',
            'multiOptions' => $this->_contributionTypes
        ));
        $this->addElement('multiCheckbox', 'participant_detail_key_id', array(
            'label' => 'Requested details from participlants',
            'multiOptions' => $this->_participantDetailKeys
        ));

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('title','description','contribution_type_id','participant_detail_key_id'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('title','description') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        if (isset($this->_entry['contribution_types'])) {
            $this->getElement('contribution_type_id')->setValue(array_keys($this->_entry['contribution_types']));
        }
        if (isset($this->_entry['participant_detail_keys'])) {
            $this->getElement('participant_detail_key_id')->setValue(array_keys($this->_entry['participant_detail_keys']));
        }
    }

}
