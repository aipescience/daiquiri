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

class Meeting_Form_Meetings extends Daiquiri_Form_Abstract {

    private $_submit;
    private $_meeting;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setMeeting($meeting) {
        $this->_meeting = $meeting;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('text', 'title', array(
            'label' => 'Name of the Meeting',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('title'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('title') as $element) {
            if (isset($this->_meeting[$element])) {
                $this->setDefault($element, $this->_meeting[$element]);
            }
        }
    }

}
