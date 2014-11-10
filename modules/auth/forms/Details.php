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

class Auth_Form_Details extends Auth_Form_Abstract {

    /**
     * The key of the user detail.
     * @var string
     */
    protected $_key;

    /**
     * The value of the user detail.
     * @var string
     */
    protected $_value;

    /**
     * The label of the submit button.
     * @var string
     */
    protected $_submit;

    /**
     * Sets $_key.
     * @param string $key the key of the user detail
     */
    public function setKey($key) {
        $this->_key = $key;
    }

    /**
     * Sets $_value.
     * @param string $value the value of the user detail
     */
    public function setValue($value) {
        $this->_value = $value;
    }

    /**
     * Sets $_submit.
     * @param string $submit the label of the submit button
     */
    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        $this->addTextElement('key', array(
            'label' => 'Key',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('alnum'),
                array('stringLength', false, array(0, 256))
            )
        ));
        $this->addDetailElement('value');
        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // set decorators
        $this->addHorizontalGroup(array('key', 'value'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_key)) {
            $this->setDefault('key', $this->_key);
            $this->setFieldReadonly('key');
        }
        if (isset($this->_value)) {
            $this->setDefault('value', $this->_value);
        }
    }

}
