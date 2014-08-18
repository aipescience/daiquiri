<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Data_Form_Tables extends Data_Form_Abstract {

    /**
     * Databases to choose from.
     * @var array
     */
    protected $_databases = array();

    /**
     * Preselected database.
     * @var int 
     */
    protected $_database_id;

    /**
     * Sets $_databases.
     * @param array $databases databases to choose from
     */
    public function setDatabases($databases) {
        $this->_databases = $databases;
    }

    /**
     * Sets $_database_id.
     * @param [type] $database_id preselected database
     */
    public function setDatabaseId($database_id) {
        $this->_database_id = $database_id;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
    
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
            'multiOptions' => $this->_roles,
        ));
        $this->addElement('checkbox', 'publication_select', array(
            'label' => 'Allow SELECT',
            'value' => '1',
            'class' => 'checkbox'
        ));
        $this->addElement('checkbox', 'publication_update', array(
            'label' => 'Allow UPDATE',
            'class' => 'checkbox'
        ));
        $this->addElement('checkbox', 'publication_insert', array(
            'label' => 'Allow INSERT',
            'class' => 'checkbox'
        ));
        if (empty($this->_entry)) {
            $this->addElement('checkbox', 'autofill', array(
                'label' => 'Autofill columns',
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
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('order', 'name', 'description', 'publication_role_id', 'publication_select','publication_update', 'publication_insert') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        if (isset($this->_database_id)) {
            $this->setDefault('database_id', $this->_database_id);
        }
    }

}
