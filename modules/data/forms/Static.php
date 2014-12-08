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

class Data_Form_Static extends Daiquiri_Form_Model {

    /**
     * The different publication roles to choose from.
     * @var array
     */
    protected $_roles = array();

    /**
     * Sets $_roles.
     * @param array $roles the different publication roles to choose from
     */
    public function setRoles($roles) {
        $this->_roles = $roles;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addElement('text', 'alias', array(
            'label' => 'Alias',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'path', array(
            'label' => 'Path',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('select', 'publication_role_id', array(
            'label' => 'Published for',
            'required' => true,
            'multiOptions' => $this->_roles,
        ));
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('alias', 'path', 'publication_role_id'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_entry)) {
            foreach (array('alias', 'path', 'publication_role_id') as $element) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
    }
}
