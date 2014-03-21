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

class Auth_Model_Resource_Apps extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Apps');
    }

    /**
     * Inserts a new row App table.
     * @param array $data
     * @throws Exception
     * @return int $id (id of the new app) 
     */
    public function insertRow($data) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // handle unencrypted password
        $data['password'] = Daiquiri_Crypt_Abstract::factory()->encrypt($data['newPassword']);

        // insert the new row
        $this->getAdapter()->insert('Auth_Apps', array(
            'appname' => $data['appname'],
            'password' => $data['password'],
            'active' => 1
        ));

        // create database for app
        if (Daiquiri_Config::getInstance()->query) {
            $userDb = Daiquiri_Config::getInstance()->getUserDbName($data['appname']);
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter('', $data['appname']);

            $sql = "CREATE DATABASE `{$userDb}`";
            $adapter->query($sql)->closeCursor();
        }

        // return the id of the newly created app
        return $this->getAdapter()->lastInsertId();
    }

}
