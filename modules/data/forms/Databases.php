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

class Data_Form_Databases extends Data_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('text', 'name', array(
            'label' => 'Database name',
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
            'label' => 'Order in list',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'int'),
            )
        ));
        $this->addElement('select', 'publication_role_id', array(
            'label' => 'Published for',
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
        $this->addElement('checkbox', 'publication_show', array(
            'label' => 'Allow SHOW TABLES',
            'value' => '1',
            'class' => 'checkbox'
        ));
        if (empty($this->_entry)) {
            $this->addElement('checkbox', 'autofill', array(
                'label' => 'Autofill tables',
                'class' => 'checkbox'
            ));
        }

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $inputelements = array('name', 'description', 'order', 'publication_role_id', 'publication_select',
            'publication_update', 'publication_insert', 'publication_show');
        if (empty($this->_entry)) {
            $inputelements[] = 'autofill';
        }
        $this->addHorizontalGroup($inputelements);
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('order', 'name', 'description', 'adapter', 'publication_role_id', 'publication_select',
    'publication_update', 'publication_insert', 'publication_show') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }

    }
}
