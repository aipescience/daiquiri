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

class Auth_Model_Resource_Details extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Details');
    }

    /**
     * Fetches a specific value details table.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @return string $detail 
     */
    public function fetchValue($userId, $key) {
        if (empty($userId) || empty($key)) {
            throw new Exception('$userId or $key not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Auth_Details', array('value'));  
        $select->where('user_id = ?', $userId);
        $select->where($this->quoteIdentifier('key') . '=?', $key);

        $row = $this->fetchOne($select);

        if ($row) {
            return $row['value'];
        } else {
            return false;
        }
    }

    /**
     * Inserts a specific value.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param string $value value of the detail
     */
    public function insertValue($userId, $key, $value) {
        if (empty($userId) || empty($key) || empty($value)) {
            throw new Exception('$userId, $key or $value not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $this->getAdapter()->insert('Auth_Details', array(
            'user_id' => $userId,
            'key' => $key,
            'value' => $value
        ));
    }

    /**
     * Updates a specific value.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param string $value value of the detail
     */
    public function updateValue($userId, $key, $value) {
        if (empty($userId) || empty($key)) {
            throw new Exception('$userId or $key not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $this->getAdapter()->update('Auth_Details', array(
            'value' => $value
        ), array(
            'user_id = ?' => $userId,
            $this->quoteIdentifier('key') . '=?' => $key,
        ));
    }

    /**
     * Deletes a specific value details table.
     * @param int $userId id of the user
     * @param string $key key of the detail
     */
    public function deleteValue($userId, $key) {
        if (empty($userId) || empty($key)) {
            throw new Exception('$userId or $key not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $this->getAdapter()->delete('Auth_Details', array(
            'user_id = ?' => $userId,
            $this->quoteIdentifier('key') . '=?' => $key
        ));
    }

    /**
     * Stores a given event as detail with the date, the ip and the user.
     * @param int $userId id of the user
     * @param string $event
     * @throws Exception
     */
    public function logEvent($userId, $event) {
        if (empty($userId) || empty($event)) {
            throw new Exception('$userId or $event not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $date = date("Y-m-d\TH:i:s");
        $ip = Daiquiri_Auth::getInstance()->getRemoteAddr();
        $user = Daiquiri_Auth::getInstance()->getCurrentUsername();

        $string = 'date:' . $date . ',ip:' . $ip . ',user:' . $user;

        $this->insertValue($userId, $event, $string);
    }

}

