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

/**
 * A validator which allows unicode letters and numbers and certain common 
 * special characters, which we consider save.
 */

/**
 * @class   Daiquiri_Form_Validator_Volatile Volatile.php
 * @brief   Validator for unicode letters and numbers and safe characters in a
 *          text field.
 * 
 * A validator which allows unicode letters and numbers and certain common 
 * special characters, which we consider save.
 * 
 * Differences to Daiquiri_Form_Validator_Text: This validator also allows \s
 * (i.e. Whitespaces).
 * 
 */
class Daiquiri_Form_Validator_Volatile extends Zend_Validate_Abstract {

    const CHARS = 'chars';

    /**
     * @var array $_messageTemplates
     * Default error message produced by this validator.
     */
    protected $_messageTemplates = array(
        self::CHARS => "some characters are not allowed"
    );

    /**
     * @brief   Checks whether given input is valid according to validator
     * @param   string $value       string to validate
     * @return  bool
     * 
     * Validates given string according to given validation. This will allow
     * unicode letters and numbers and certain common special characters, which 
     * we consider save in text areas.
     * 
     * Returns true if validation is positive.
     */
    public function isValid($value) {
        $this->_setValue($value);

        $isValid = true;

        if (preg_match("/[^ \<\>\s\/\,\;\.\'\"\-\=\?\!\@\#\$\%\^\&\*\(\)\]\[\}\{\_\+\:\`\p{L}0-9]/u", $value)) {
            $this->_error(self::CHARS);
            $isValid = false;
        }

        return $isValid;
    }

}
