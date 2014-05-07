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

class Auth_Model_Resource_Apps extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Apps');
    }

    /**
     * Inserts a new row into the App table and create the corresponding user table.
     * @param array $data
     * @throws Exception
     * @return int $id id of the new app
     */
    public function insertRow(array $data = array()) {
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
