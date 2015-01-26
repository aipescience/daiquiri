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
 * @class   Daiquiri_Crypt_CryptSha512 CryptSha512.php
 * @brief   Encryption class for unix crypt encryption with SHA 512
 * 
 * Encryption class for unix crypt encryption with SHA 512. This is the equivalent
 * to what is in the unix password file.
 * 
 */
class Daiquiri_Crypt_CryptSha512 extends Daiquiri_Crypt_Abstract {

    /**
     * @brief   implementation of unix crypt encryption with SHA 512
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Implementation of unix crypt encryption with SHA 512 in a form equal to
     * the unix password file. Random salting is applied...
     * 
     */
    public function encrypt($string) {
        return crypt($string, '$6$' . self::generateSalt() . '$');
    }

    /**
     * @brief   unix crypt encryption in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * Returns SQL statement with equivalent to unix crypt (ENCRYPT).
     * 
     */
    public function getTreatment() {

        return 'ENCRYPT(?, CONCAT("$6$",right(left(password,11),8),"$"))';
    }

}
