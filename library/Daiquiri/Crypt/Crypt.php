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
 * @class   Daiquiri_Crypt_Crypt Crypt.php
 * @brief   Encryption class for unix crypt encryption
 * 
 * Encryption class for unix crypt encryption.
 * 
 */
class Daiquiri_Crypt_Crypt extends Daiquiri_Crypt_Abstract {

    /**
     * @brief   implementation of unix crypt encryption
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Implementation of unix crypt encryption.
     * 
     */
    public function encrypt($string) {
        if ($this->_salt) {
            return crypt($string, $this->_salt);
        } else {
            return crypt($string);
        }
    }

    /**
     * @brief   unix crypt encryption in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * Returns SQL statement with equivalent to unix crypt (ENCRYPT).
     * 
     */
    public function getTreatment() {

        if ($this->_salt) {
            return 'ENCRYPT(?,"' . $this->_salt . '")';
        } else {
            return 'ENCRYPT(?)';
        }
    }

}
