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

class Query_Form_UpdateExample extends Daiquiri_Form_Abstract {

    protected $_example = null;
    protected $_roles = array();

    public function setExample($example) {
        $this->_example = $example;
    }

    public function setRoles($roles) {
        $this->_roles = $roles;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
        $this->addElement('text', 'name', array(
            'label' => 'Name',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('textarea', 'query', array(
            'label' => 'Query',
            'class' => 'input-xxlarge',
            'rows' => '6',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('textarea', 'description', array(
            'label' => 'Description (optional)',
            'class' => 'input-xxlarge',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('select', 'publication_role_id', array(
            'label' => 'Published for',
            'required' => true,
            'multiOptions' => $this->_roles,
        ));
        $this->addPrimaryButtonElement('submit', 'Update Example');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('name', 'query', 'description', 'publication_role_id'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_example)) {
            foreach (array('name', 'query', 'description', 'publication_role_id') as $element) {
                $this->setDefault($element, $this->_example[$element]);
            }
        }
    }
}
