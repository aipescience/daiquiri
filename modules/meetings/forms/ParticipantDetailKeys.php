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

class Meetings_Form_ParticipantDetailKeys extends Daiquiri_Form_Model {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addTextElement('key', array(
            'label' => 'Participant detail key',
            'class' => 'span6 mono',
            'required' => true,
            'filters' => array(
                'StringTrim',
                array('PregReplace', array('match' => '/ /', 'replace' => '_'))
            ),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_AlnumUnderscore()),
            )
        ));
        $this->addTextElement('hint', array(
            'label' => 'Hint for form',
            'class' => 'span6',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addSelectElement('type_id', array(
            'label' => 'Type',
            'required' => true,
            'filters' => array('StringTrim'),
            'multiOptions' => Meetings_Model_ParticipantDetailKeys::$types
        ));
        $this->addTextElement('options', array(
            'label' => 'Options',
            'class' => 'span6 mono',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Json()),
            )
        ));
        $this->addCheckboxElement('required', array(
            'label' => 'Required'
        ));

        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('key','hint','type_id','options','required'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('key','hint','type_id','options','required') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
    }

}
