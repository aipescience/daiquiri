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

class Auth_Model_Resource_Details extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Details');
    }

    /**
     * Fetches a specific value from the details table.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @return string $value value of the detail
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
     * Inserts a specific value into the details table.
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
     * Updates a specific datail.
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
     * Deletes a specific value from the details table.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @throws Exception
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

