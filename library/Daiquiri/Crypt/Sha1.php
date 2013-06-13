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
