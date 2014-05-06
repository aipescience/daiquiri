<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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
 * @class   Daiquiri_Form_Validator_Sql Sql.php
 * @brief   Validator for sql identifiers.
 * 
 * Validator for sql identifiers for databases, tables, and columns.
 *
 */
class Daiquiri_Form_Validator_Sql extends Zend_Validate_Abstract {

    const CHARS = 'chars';

    /**
     * @var array $_messageTemplates
     * Default error message produced by this validator.
     */
    protected $_messageTemplates = array(
        self::CHARS => "{ } are not allowed"
    );

    /**
     * @brief   Checks whether given input is valid according to validator
     * @param   string $value       string to validate
     * @return  bool
     * 
     * Returns true if validation is positive.
     */
    public function isValid($value) {
        $this->_setValue($value);

        $isValid = true;

        if (preg_match("/[^\(\)\[\]\/\-\_\+\:\-\<\>\!a-zA-Z0-9]/", $value)) {
            $this->_error(self::CHARS);
            $isValid = false;
        }

        return $isValid;
    }

}
