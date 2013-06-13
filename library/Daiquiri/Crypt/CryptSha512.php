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
