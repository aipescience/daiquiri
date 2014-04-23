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
 * @class   Daiquiri_Crypt_Abstract Abstract.php
 * @brief   Abstract base class for all crypt models in the daiquiri framework
 * 
 * Abstract base class that contains the Crypt factory for a requested crypt
 * method and the salt generation algorithm.
 * 
 * Any derived class needs to implement the encryption algorithm.
 * 
 */
abstract class Daiquiri_Crypt_Abstract {

    protected $_salt;           //!< bool encoding if salting of passwords is enabled

    /**
     * @brief   factory method for specified crypt algorithm object
     * @param   string $algorithm: name of crypt algorithm
     * @return  Daiquiri_Crypt_Abstract instance of initialised crypt object
     * 
     * Returns an initialised and newly allocated crypt object with the specified
     * algorithm. An implementation of a given algorithm must have the same name
     * as the one given by the $algorithm parameter. If no algorithm is given, a
     * default one is used. 
     * 
     * The default algorithm is set in daiquiri.ini as auth.password.default.algo
     * 
     */

    static function factory($algorithm = 'default') {

        $authConfig = Daiquiri_Config::getInstance()->auth;
        if ($authConfig == Null) {
            // values are not set in the configuration
            throw new Daiquiri_Exception_Forbidden();
        }

        // get the values from the config
        $cryptConfig = $authConfig->password->$algorithm;
        $algo = $cryptConfig->algo;

        if ($cryptConfig->salt) {
            $salt = $cryptConfig->salt;
        } else {
            $salt = null;
        }

        // get the name of the class
        $className = 'Daiquiri_Crypt_' . ucfirst($algo);

        if (is_subclass_of($className, 'Daiquiri_Crypt_Abstract')) {
            return new $className($salt);
        } else {
            throw new Exception('Unknown hashing algorithm in ' . __METHOD__);
        }
    }

    /**
     * @brief   generate a random string for salting of passwords
     * @param   string $length: length of random string
     * @return  string of random numbers and lower/upper case characters
     * 
     * Generates a random string for salting passwords of a given length. If no
     * length is given, a string of length 8 is generated. 
     * 
     */
    static function generateSalt($length = 8) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            $s .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $s;
    }

    /**
     * @brief   constructor
     * @param   bool $salt: enables salting of passwords
     *  
     * Final constructor for this class.
     * 
     */
    final public function __construct($salt = null) {
        $this->_salt = $salt;
    }

    /**
     * @brief   abstract definition of function returning encrypted string
     * @param   string $string: string to encrypt
     * @return  string: the encrypted string 
     * 
     * Needs to be implemented by inheriting class. This is where the encryption
     * algorithm goes to. It takes a string and returns an encrypted version.
     * 
     */
    abstract public function encrypt($string);

    /**
     * @brief   abstract definition of function returning equivalent encryption
     *          method in SQL
     * @return  string: SQL statement for encryption on the database
     *  
     * This function returns a string with an SQL statement, that performs the
     * implemented encryption algorithm on the database server. If a salt is 
     * given, the function must inclulde/add/concatenate the salt to the provided
     * password.
     * 
     */
    abstract public function getTreatment();
}

