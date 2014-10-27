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

class Auth_Form_Element_Email extends Daiquiri_Form_Element_Text {

    /**
     * Exclude a certain id from the validator.
     * @var int
     */
    private $_excludeId = false;

    /**
     * Sets $_excludeId.
     * @param bool $unique exclude a certain id from the validator.
     */
    public function setExcludeId($excludeId) {
        $this->_excludeId = $excludeId;
    }

    /**
     * Construtor. Sets the name of the element.
     * @param array $options form options for this element
     */
    public function __construct($options = null) {
        parent::__construct('email', $options);
    }

    /**
     * Initializes the form element.
     */
    function init() {
        // set label
        $this->setLabel('Email');

        // set required
        $this->setRequired(true);

        // set filter
        $this->addFilter('StringTrim');

        // add validator for max string length
        $this->addValidator('StringLength', false, array(0, 256));

        // add validator for email addresses
        $emailValidator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL);
        $this->addValidator($emailValidator);

        // add validator for beeing unique in the database
        $validator = new Zend_Validate();
        $message = 'The email is already in the database, please check if you are already registered.';

        $userTableValidator = new Zend_Validate_Db_NoRecordExists('Auth_User', 'email');
        $userTableValidator->setMessage($message);
        if (!empty($this->_excludeId)) {
            $userTableValidator->setExclude(array(
                'field' => 'id',
                'value' => $this->_excludeId
            ));
        }

        $registrationTableValidator = new Zend_Validate_Db_NoRecordExists('Auth_Registration', 'email');
        $registrationTableValidator->setMessage($message);

        // chainvalidators and add to field
        $validator->addValidator($userTableValidator)->addValidator($registrationTableValidator);
        $this->addValidator($validator);
    }
}
