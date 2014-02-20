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
 * @class   Daiquiri_Crypt_Crypt Crypt.php
 * @brief   Encryption class for unix crypt encryption
 * 
 * Encryption class for unix crypt encryption.
 * 
 */
class Daiquiri_Crypt_Htpasswd extends Daiquiri_Crypt_Abstract {

    /**
     * @brief   implementation of htpasswd encryption
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Implementation of htpasswd encryption.
     * 
     */
    public function encrypt($string) {
        return crypt($string, base64_encode($string));
    }

    /**
     * @brief   htpasswd encryption in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * Returns SQL statement with equivalent to htpasswd.
     */
    public function getTreatment() {
        return 'ENCRYPT(?,TO_BASE64(?))';
    }

}