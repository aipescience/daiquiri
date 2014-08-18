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

class Core_Form_Config extends Daiquiri_Form_Model {

    /**
     * Initializes the form.
     */
    public function init() {
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
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('key', 'value'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_entry['key'])) {
            $this->setDefault('key', $this->_entry['key']);
        }
        if (isset($this->_entry['value'])) {
            $this->setDefault('value', $this->_entry['value']);
        }
    }

}
