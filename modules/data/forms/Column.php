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

class Data_Form_Column extends Daiquiri_Form_Abstract {

    protected $_tables = array();
    protected $_table_id = null;
    protected $_entry = array();
    protected $_submit = null;
    protected $_csrfActive = true;

    public function setTables($tables) {
        $this->_tables = $tables;
    }

    public function setTableId($table_id) {
        $this->_table_id = $table_id;
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
        $this->addElement('select', 'table_id', array(
            'label' => 'Table:',
            'required' => true,
            'multiOptions' => $this->_tables
        ));
        $this->addElement('text', 'position', array(
            'label' => 'Position of column:',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'int'),
            )
        ));
        $this->addElement('text', 'name', array(
            'label' => 'Column name:',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('text', 'type', array(
            'label' => 'Column type:',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('text', 'unit', array(
            'label' => 'Column unit:',
            'class' => 'input-xxlarge',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addElement('text', 'ucd', array(
            'label' => 'Column UCD:',
            'class' => 'input-xxlarge',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));

        //obtain UCD data and provide a usable form (but only if called from a non scriptable context)
        $ucdStrings = array();

        if($this->_csrfActive === true) {
            $ucdResource = new Data_Model_Resource_UCD;
            $ucdData = $ucdResource->getTable()->fetchAll()->toArray();

            foreach ($ucdData as $ucd) {
                $ucdStrings[$ucd['word']] = $ucd['word'] . " | " . $ucd['type'] . " | " . $ucd['description'];
            }
        }

        $this->addElement('select', 'ucd_list', array(
            'label' => 'List of UCDs: ',
            'required' => false,
            'multiOptions' => $ucdStrings,
        ));
        $this->addElement('textarea', 'description', array(
            'label' => 'Column description',
            'class' => 'input-xxlarge',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('table_id', 'position', 'name', 'type', 'unit', 'ucd', 'ucd_list', 'description'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('table_id', 'position', 'name', 'type', 'unit', 'ucd', 'description') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        if (isset($this->_table_id)) {
            $this->setDefault('table_id', $this->_table_id);
        }
    }

}
