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
 * Resource class for the application management.
 */
class Auth_Model_Resource_Apps extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->setTable('Auth_Model_DbTable_Apps');
    }

    /**
     * Stores a new app.
     * @param array $credentials
     * @return int $id (id of the new app) 
     */
    public function storeApp($credentials) {
        // handle unencrypted password
        if (!empty($credentials['newPassword'])) {
            // crypt new password
            $crypt = Daiquiri_Crypt_Abstract::factory();
            $credentials['password'] = $crypt->encrypt($credentials['newPassword']);
        }

        // get the user table
        $table = $this->getTable('Auth_Model_DbTable_Apps');

        // insert the new row
        $table->insert(array(
            'appname' => $credentials['appname'],
            'password' => $credentials['password'],
            'active' => 1
        ));

        // create database for app
        if (Daiquiri_Config::getInstance()->query
                && Daiquiri_Config::getInstance()->query->userDb) {
            $userDb = Daiquiri_Config::getInstance()->getUserDbName($credentials['appname']);
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($credentials['appname'], '');

            $sql = "CREATE DATABASE `{$userDb}`";
            $adapter->query($sql)->closeCursor();
        }

        // return the id of the newly created app
        return $table->getAdapter()->lastInsertId();
    }

}
