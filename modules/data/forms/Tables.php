<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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
     * The set of databases to choose from.
     * @var array
     */
    protected $_databases = array();

    /**
     * The preselected database.
     * @var int
     */
    protected $_database_id;

    /**
     * Sets $_databases.
     * @param array $databases the set of databases to choose from
     */
    public function setDatabases($databases) {
        $this->_databases = $databases;
    }

    /**
     * Sets $_database_id.
     * @param [type] $database_id the preselected database
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
        $this->addSelectElement('database_id', array(
            'label' => 'Database:',
            'required' => true,
            'multiOptions' => $this->_databases
        ));
        $this->addTextElement('name', array(
            'label' => 'Table name:',
            'required' => true,
            'class' => 'span6 mono',
            'filters' => array('StringTrim'),
            'class' => 'span6',
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextareaElement('description', array(
            'label' => 'Database description',
            'class' => 'span6 mono',
            'rows' => '4',
            'filters' => array('StringTrim'),
            'class' => 'span6',
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));
        $this->addTextElement('order', array(
            'label' => 'Order',
            'class' => 'span1 mono',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'int'),
            )
        ));
        $this->addSelectElement('publication_role_id', array(
            'label' => 'Published for',
            'required' => true,
            'multiOptions' => $this->_roles,
        ));
        $this->setDefault('publication_role_id', 0);
        $this->addCheckboxElement('publication_select', array(
            'label' => 'Allow SELECT',
            'value' => '1',
            'class' => 'checkbox'
        ));
        $this->addCheckboxElement('publication_update', array(
            'label' => 'Allow UPDATE',
            'class' => 'checkbox'
        ));
        $this->addCheckboxElement('publication_insert', array(
            'label' => 'Allow INSERT',
            'class' => 'checkbox'
        ));
        if (empty($this->_entry)) {
            $this->addCheckboxElement('autofill', array(
                'label' => 'Autofill columns',
                'class' => 'checkbox'
            ));
        }

        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $inputelements = array('database_id', 'name', 'description', 'order', 'publication_role_id', 'publication_select',
            'publication_update', 'publication_insert');
        if (empty($this->_entry)) {
            $inputelements[] = 'autofill';
        }
        $this->addHorizontalGroup($inputelements);
        $this->addActionGroup(array('submit', 'cancel'));

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
