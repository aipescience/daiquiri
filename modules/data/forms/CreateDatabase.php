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

class Data_Form_CreateDatabase extends Daiquiri_Form_Abstract {

    protected $_roles = array();
    protected $_adapter = array();

    public function setRoles($roles) {
        $this->_roles = $roles;
    }

    public function setAdapter($adapter) {
        $this->_adapter = $adapter;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('text', 'name', array(
            'label' => 'Database name',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Sql()),
            )
        ));
        $this->addElement('textarea', 'description', array(
            'label' => 'Database description',
            'class' => 'input-xxlarge',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));
        $this->addElement('select', 'adapter', array(
            'label' => 'Zend database adapter',
            'required' => true,
            'multiOptions' => $this->_adapter,
        ));
        $this->addElement('select', 'publication_role_id', array(
            'label' => 'Published for',
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
        $this->addElement('checkbox', 'publication_show', array(
            'label' => 'Allow SHOW TABLES',
            'required' => true,
            'class' => 'checkbox'
        ));
        if (empty($this->_entry)) {
            $this->addElement('checkbox', 'autofill', array(
                'label' => 'Autofill tables',
                'required' => false,
                'class' => 'checkbox'
            ));
        }

        $this->addPrimaryButtonElement('submit', 'Create database entry');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $inputelements = array('name', 'description', 'adapter', 'publication_role_id', 'publication_select',
            'publication_update', 'publication_insert', 'publication_show');
        if (empty($this->_entry)) {
            $inputelements[] = 'autofill';
        }
        $this->addHorizontalGroup($inputelements);
        $this->addActionGroup(array('submit', 'cancel'));
    }

}
