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
 * @class   Daiquiri_Crypt_Plain Plain.php
 * @brief   Encryption class for insecure plaintext storage
 * 
 * Encryption class for insecure plaintext storage
 * 
 */
class Daiquiri_Crypt_Plain extends Daiquiri_Crypt_Abstract {

    /**
     * @brief   implementation of insecure plaintext storage.
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Implementation of insecure plaintext storage.
     * 
     */
    public function encrypt($string) {
        if ($this->_salt) {
            return $string . $this->_salt;
        } else {
            return $string;
        }
    }

    /**
     * @brief   unix crypt encryption in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * Returns SQL that just stores the password in insecure platext
     * way!
     * 
     */
    public function getTreatment($salt = '') {
        if ($this->_salt) {
            return 'CONCAT(?,"' . $this->_salt . '")';
        } else {
            return "(?)";
        }
    }

}
