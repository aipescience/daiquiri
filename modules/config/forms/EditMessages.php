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

class Config_Form_EditMessages extends Daiquiri_Form_Abstract {

    protected $_key = null;
    protected $_value = null;

    public function setKey($key) {
        $this->_key = $key;
    }

    public function setValue($value) {
        $this->_value = $value;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
        $this->addElement('textarea', 'value', array(
            'label' => ucfirst($this->_key),
            'class' => 'input-xxlarge',
            'rows' => '4',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
                array('StringLength' => new Zend_Validate_StringLength(array('max' => 256)))
            )
        ));


        $this->addPrimaryButtonElement('submit', 'Edit config entry');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('value'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_value)) {
            $this->setDefault('value', $this->_value);
        }
    }

}
