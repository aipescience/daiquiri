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
class Daiquiri_Form_Validator_Tablename extends Zend_Validate_Abstract {

    const CHARS = 'chars';
    const LENGTH = 'length';

    protected $_messageTemplates = array(
        self::CHARS => "Only digits, letters and [ ] < > + - _ , : are allowed",
        self::LENGTH => "The tablename must be shorter than 128 characters"
    );

    public function isValid($value) {
        $this->_setValue($value);

        $isValid = true;

        // preg_match("/[^ \<\>\s\/\,\.\-\=\#\$\^\]\[\}\{\_\+\:"
        // '/^[^;@%*?()!"`\'&]+$/'

        if (preg_match("/[^A-Za-z0-9\,\_\:\]\[\<\>\+\-]/", $value)) {
            $this->_error(self::CHARS);
            $isValid = false;
        }

        if (strlen($value) > 128) {
            $this->_error(self::LENGTH);
            $isValid = false;
        }

        return $isValid;
    }

}