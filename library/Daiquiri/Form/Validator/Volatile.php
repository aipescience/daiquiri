<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
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
