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

class Query_Form_RenameJob extends Daiquiri_Form_Abstract {

    private $_tablename;

    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add fields
        $this->addElement('text', 'tablename', array(
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(1, 128)),
                array('validator' => 'Regex', 'options' => array('pattern' => '/^[^;@%*?()!"`\'&]+$/'))
            ),
            'label' => 'New name of the table',
            'value' => $this->_tablename,
            'required' => true
        ));

        $this->addPrimaryButtonElement('submit', 'Rename table');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('tablename'), 'tablerename-group');
        $this->addActionGroup(array('submit', 'cancel'));
    }

}
