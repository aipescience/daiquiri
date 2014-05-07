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
 * @class   Daiquiri_Crypt_Sha1 Sha1.php
 * @brief   Encryption class for SHA1 hashing
 * 
 * Encryption class for SHA1 hashing.
 * 
 */
class Daiquiri_Crypt_Sha1 extends Daiquiri_Crypt_Abstract {

    /**
     * @brief   implementation of SHA1 hashing
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Implementation of SHA1 hashing.
     * 
     */
    public function encrypt($string) {
        if ($this->_salt) {
            return sha1($string . $this->_salt);
        } else {
            return sha1($string);
        }
    }

    /**
     * @brief   unix crypt encryption in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * Returns SQL statement with equivalent to unix crypt (SHA1).
     * 
     */
    public function getTreatment() {
        if ($this->_salt) {
            return 'SHA1(CONCAT(?,"' . $this->_salt . '"))';
        } else {
            return 'SHA1(?)';
        }
    }

}
