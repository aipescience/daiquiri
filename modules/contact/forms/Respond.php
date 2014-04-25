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

class Contact_Form_Respond extends Daiquiri_Form_Abstract {

    protected $_subject;
    protected $_body;

    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    public function setBody($body) {
        $this->_body = $body;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
        $this->addElement('text', 'subject', array(
            'label' => 'Subject',
            'class' => 'input-xxlarge mono',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('textarea', 'body', array(
            'label' => 'Message',
            'class' => 'input-xxlarge mono',
            'rows' => '10',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea())
            )
        ));

        $this->addPrimaryButtonElement('submit', 'Respond to contact message');
        $this->addButtonElement('cancel', 'Cancel');

        // create groups
        $this->addHorizontalGroup(array('subject', 'body'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set default values for fields.
        $this->setDefault('subject', $this->_subject);
        $this->setDefault('body', $this->_body);
    }

}
