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

class Config_Form_Entries extends Daiquiri_Form_Abstract {

    private $_submit = null;
    private $_entry = null;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }
    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('text', 'key', array(
            'label' => 'Key',
            'class' => 'input-xxlarge mono',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('StringLength' => new Zend_Validate_StringLength(array('max' => 256)))
            )
        ));
        $this->addElement('textarea', 'value', array(
            'label' => 'Value',
            'class' => 'input-xxlarge mono',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('StringLength' => new Zend_Validate_StringLength(array('max' => 256)))
            )
        ));
        $this->addPrimaryButtonElement('submit', 'Create config entry');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('key', 'value'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_entry['key'])) {
            $this->setDefault('key', $this->_entry['key']);
        }
        if (isset($this->_entry['value'])) {
            $this->setDefault('value', $this->_entry['value']);
        }
    }

}
