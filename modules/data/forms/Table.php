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

class Data_Form_Table extends Daiquiri_Form_Abstract {

    protected $_databases = array();
    protected $_database_id;
    protected $_roles = array();
    protected $_entry = array();
    protected $_submit;
    protected $_csrfActive = true;

    public function setDatabases($databases) {
        $this->_databases = $databases;
    }

    public function setDatabaseId($database_id) {
        $this->_database_id = $database_id;
    }

    public function setRoles($roles) {
        $this->_roles = $roles;
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setCsrfActive($csrfActive) {
        $this->_csrfActive = $csrfActive;
    }

    public function init() {
        $this->setFormDecorators();

        if($this->_csrfActive === true) {
            $this->addCsrfElement();
        }
        
        // add elements
        $this->addElement('select', 'database_id', array(
            'label' => 'Database:',
            'required' => true,
            'multiOptions' => $this->_databases
        ));
        $this->addElement('text', 'name', array(
            'label' => 'Table name:',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('textarea', 'description', array(
            'label' => 'Database description',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('text', 'order', array(
            'label' => 'Order',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'int'),
            )
        ));
        $this->addElement('select', 'publication_role_id', array(
            'label' => 'Published for: ',
            'required' => true,
            'multiOptions' => $this->_roles,
        ));
        $this->addElement('checkbox', 'publication_select', array(
            'label' => 'Allow SELECT',
            'required' => true,
            'class' => 'checkbox'
        ));
        $this->addElement('checkbox', 'publication_update', array(
            'label' => 'Allow UPDATE',
            'required' => true,
            'class' => 'checkbox'
        ));
        $this->addElement('checkbox', 'publication_insert', array(
            'label' => 'Allow INSERT',
            'required' => true,
            'class' => 'checkbox'
        ));
        if (empty($this->_entry)) {
            $this->addElement('checkbox', 'autofill', array(
                'label' => 'Autofill columns',
                'required' => false,
                'class' => 'checkbox'
            ));
        }

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $inputelements = array('database_id', 'name', 'description', 'order', 'publication_role_id', 'publication_select',
            'publication_update', 'publication_insert');
        if (empty($this->_entry)) {
            $inputelements[] = 'autofill';
        }
        $this->addHorizontalGroup($inputelements);
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('order', 'name', 'description', 'publication_role_id', 'publication_select',
    'publication_update', 'publication_insert') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        if (isset($this->_database_id)) {
            $this->setDefault('database_id', $this->_database_id);
        }
    }

}
