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
