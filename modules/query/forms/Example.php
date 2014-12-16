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

class Query_Form_Example extends Daiquiri_Form_Model {

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
        $this->addTextElement('name', array(
            'label' => 'Name',
            'class' => 'span6',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextareaElement('query', array(
            'label' => 'Query',
            'class' => 'span6',
            'rows' => '6',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addTextareaElement('description', array(
            'label' => 'Description (optional)',
            'class' => 'span6',
            'rows' => '4',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Volatile()),
            )
        ));
        $this->addSelectElement('publication_role_id', array(
            'label' => 'Published for',
            'required' => true,
            'multiOptions' => $this->_roles,
        ));
        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('name', 'query', 'description', 'publication_role_id'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_entry)) {
            foreach (array('name', 'query', 'description', 'publication_role_id') as $element) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
    }
}
