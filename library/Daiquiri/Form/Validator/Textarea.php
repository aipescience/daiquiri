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

/**
 * Validator class for regular multi line text input fields.
 */
class Daiquiri_Form_Validator_Textarea extends Zend_Validate_Abstract {

    const CHARS = 'chars';

    /**
     * Default error message produced by this validator.
     * @var array $_messageTemplates
     */
    protected $_messageTemplates = array(
        self::CHARS => "Some special Character are not allowed"
    );

    /**
     * Checks whether given input is valid according to validator. Allowed are:
     * Unicode letters, digits, spaces, newlines and special characters (.,:;?!'"'+-\*=@#$%^`&()[]{}<>_~)
     * @param  string $value string to validate
     * @return bool
     */
    public function isValid($value) {
        $this->_setValue($value);

        $isValid = true;
        if (preg_match("/[^ \.\,\:\;\?\!\'\"\'\+\-\/\*\=\@\#\$\%\^\`\&\(\)\[\]\{\}\<\>\_\~\p{L}0-9\s]/u", $value)) {
            $this->_error(self::CHARS);
            $isValid = false;
        }

        return $isValid;
    }

}
