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

abstract class Auth_Form_Element_AbstractName extends Zend_Form_Element_Text {

    /**
     * Exclude a certain id from the unique-ness validator.
     * @var int
     */
    protected $_excludeId = false;

    /**
     * Sets $_excludeId.
     * @param bool $unique exclude a certain id from the unique-ness validator.
     */
    public function setExcludeId($excludeId) {
        $this->_excludeId = $excludeId;
    }

    /**
     * Initializes the form element.
     */
    function init() {
        // set filter
        $this->addFilter('StringTrim');

        // set required
        $this->setRequired(true);

        // set label
        $this->setLabel(ucfirst($this->getName()));

        // set validator for lowercase or regular alnum
        if (Daiquiri_Config::getInstance()->auth->lowerCaseUsernames) {
            $this->addValidator(new Daiquiri_Form_Validator_LowerCaseAlnum());
        } else {
            $this->addValidator(new Daiquiri_Form_Validator_AlnumUnderscore());
        }

        // add validator for min and max string length
        $minLength = Daiquiri_Config::getInstance()->auth->usernameMinLength;
        $this->addValidator('StringLength', false, array($minLength, 256));

        // add validator for beeing unique in the database
        $validator = new Zend_Validate();
        $message = 'The username is in use, please use another username.';

        $userTableValidator = new Zend_Validate_Db_NoRecordExists('Auth_User', 'username');
        $userTableValidator->setMessage($message);
        if (!empty($this->_excludeId)) {
            $userTableValidator->setExclude(array(
                'field' => 'id',
                'value' => $this->_excludeId
            ));
        }

        $registrationTableValidator = new Zend_Validate_Db_NoRecordExists('Auth_Registration', 'username');
        $registrationTableValidator->setMessage($message);

        $appTableValidator = new Zend_Validate_Db_NoRecordExists('Auth_Apps', 'appname');
        $appTableValidator->setMessage($message);

        $validator->addValidator($userTableValidator)->addValidator($registrationTableValidator)->addValidator($appTableValidator);

        $this->addValidator($validator);
    }
}
