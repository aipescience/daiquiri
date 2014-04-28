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

class Auth_Model_Resource_User extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_User');
    }

    /**
     * Returns the colums of (joined) Auth tables.
     * @return array $cols
     */
    public function fetchCols() {
        $cols = array('id', 'username', 'email', 'role', 'status');
        foreach (Daiquiri_Config::getInstance()->auth->details as $key) {
            if (substr($key, 0, 8) !== 'password') {
                $cols[] = $key;
            }
        }
        return $cols;
    }

    /**
     * Fetches a set of rows from the (joined) Auth tables specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from('Auth_User', array('id','username','email','role_id','status_id'));
        $select->join('Auth_Roles', '`Auth_Roles`.`id` = `Auth_User`.`role_id`', array('role'));
        $select->join('Auth_Status', '`Auth_Status`.`id` = `Auth_User`.`status_id`', array('status'));

        // add an left join for every detail key
        foreach (Daiquiri_Config::getInstance()->auth->details as $key) {
            if (substr($key, 0, 8) !== 'password') {
                $detailSelect = $this->select();
                $detailSelect->from('Auth_Details', array('user_id', 'value'));
                $detailSelect->where('`key` = ?', $key);
                $select->joinLeft(array('tmp_' . $key => $detailSelect), "`Auth_User`.`id` = tmp_" . $key . '.user_id', array($key => 'value'));
            }
        }

        // get the rowset and return
        return $this->fetchAll($select);
    }

    /**
     * Fetches a specific row from the (joined) Auth tables.
     * @param mixed $input primary key of the row OR array of sqloptions (start,limit,order,where)
     * @throws Exception
     * @return array $row 
     */
    public function fetchRow($input) {
        if (empty($input)) {
            throw new Exception('$id or $sqloptions not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }
        if (is_array($input)) {
            $select = $this->select($input);
        } else {
            $select = $this->select();
            $select->where('Auth_User.id = ?', $input);
        }
        $select->from('Auth_User', array('id','username','email','role_id','status_id'));
        $select->join('Auth_Roles', '`Auth_Roles`.`id` = `Auth_User`.`role_id`', array('role'));
        $select->join('Auth_Status', '`Auth_Status`.`id` = `Auth_User`.`status_id`', array('status'));

        $row = $this->fetchOne($select);

        if ($row) {
            $select = $this->select();
            $select->from('Auth_Details', array('key','value'));
            $select->where('user_id = ?', $row['id']);
            $details = $this->fetchPairs($select);

            // unset passwords
            foreach ($details as $key => $value) {
                if (substr($key, 0, 8) === 'password') {
                    unset($details[$key]);
                }
            }

            return array_merge($row, $details);
        } else {
            return false;
        }
    }

    /**
     * Counts the number of rows in the (joined) Auth tables.
     * @param @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from('Auth_User', 'COUNT(*) as count');
        $select->join('Auth_Roles', '`Auth_Roles`.`id` = `Auth_User`.`role_id`', array('role'));
        $select->join('Auth_Status', '`Auth_Status`.`id` = `Auth_User`.`status_id`', array('status'));

        // query database
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }

    /**
     * Inserts a new user in the (joined) Auth tables.
     * Returns the primary key of the new row.
     * @param array $data row data
     * @throws Exception
     * @return int $id id of the new user
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if (isset($data['newPassword'])) {
            // handle password
            $data['password'] = Daiquiri_Crypt_Abstract::factory()->encrypt($data['newPassword']);

            // handle additional versions of the password
            foreach (array_keys(Daiquiri_Config::getInstance()->auth->password->toArray()) as $type) {
                if ($type != 'default') {
                    $data['password_' . $type] = Daiquiri_Crypt_Abstract::factory($type)->encrypt($data['newPassword']);
                }
            }

            // unset password
            unset($data['newPassword']);
        }

        // seperate primary credentials and details
        $credentials = array();
        $details = array();
        foreach ($data as $key => $value) {
            if (in_array($key, array('username', 'email', 'password', 'status_id', 'role_id'))) {
                $credentials[$key] = $value;
            } else {
                $details[$key] = $value;
            }
        }

        // insert primary credentials
        $this->getAdapter()->insert('Auth_User', $credentials);

        // get the id of the user just inserted
        $id = $this->getAdapter()->lastInsertId();

        // insert user details
        foreach ($details as $key => $value) {
            $this->getAdapter()->insert('Auth_Details', array(
                'user_id' => $id,
                'key' => $key,
                'value' => $value
            ));
        }

        // create database for user
        if (Daiquiri_Config::getInstance()->query) {
            $userDb = Daiquiri_Config::getInstance()->getUserDbName($credentials['username']);
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter('', $credentials['username']);

            $sql = "CREATE DATABASE `{$userDb}`";
            $adapter->query($sql)->closeCursor();
        }

        // return the id of the newly created user
        return $id;
    }

    /**
     * Updates a row in the (joined) Auth tables according to the array $data.
     * @param int $id id of the user
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, array $data = array()) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // seperate primary credentials and details and check for password
        $credentials = array();
        $details = array();
        foreach ($data as $key => $value) {
            if (substr($key, 0, 8) === 'password') {
                throw new Exception('password in $data array in ' . get_class($this) . '::' . __FUNCTION__ . '()');
            } else if (in_array($key, array('username', 'email', 'status_id', 'role_id'))) {
                $credentials[$key] = $value;
            } else {
                $details[$key] = $value;
            }
        }

        // update primary credentials
        $this->getAdapter()->update('Auth_User', $credentials, array('id = ?' => $id));

        // update user details
        foreach ($details as $key => $value) {
            $this->getAdapter()->update('Auth_Details', array(
                'value' => $value
            ), array(
                'user_id=?' => $id,
                $this->quoteIdentifier('key') .  '=?' => $key
            ));
        }
    }

    /**
     * Returns the hashed default (or a specific) password of a given user.
     * @param int $id id of the user
     * @param string $type
     * @return string $password
     */
    public function fetchPassword($id, $type = 'default') {
        if (empty($id) || empty($type)) {
            throw new Exception('$id or $type not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if ($type === 'default') {
            // handle main (default) password
            $select = $this->select();
            $select->from('Auth_User', array('password'));  
            $select->where('id = ?', $id);

            $row = $this->fetchOne($select);

            if ($row) {
                return $row['password'];
            } else {
                return false;
            }
        } else {
            // handle additional versions of the password
            $select = $this->select();
            $select->from('Auth_Details', array('value'));  
            $select->where('user_id = ?', $id);
            $select->where($this->quoteIdentifier('key') .  '=?', 'password_' . $type);

            $row = $this->fetchOne($select);

            if ($row) {
                return $row['value'];
            } else {
                return false;
            }
        }
    }

    /**
     * Updates the hashed password of a given user, overwriting an old one.
     * @param int $id id of the user
     * @param string $newPassword
     * @throws Exception
     */
    public function updatePassword($id, $newPassword) {
        if (empty($id) || empty($newPassword)) {
            throw new Exception('$id or $newPassword not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // handle main (default) password
        $hash = Daiquiri_Crypt_Abstract::factory()->encrypt($newPassword);

        // update user table in database
        $this->getAdapter()->update('Auth_User', array('password' => $hash), array('id = ?' => $id));

        // handle additional versions of the password
        foreach (Daiquiri_Config::getInstance()->auth->password as $type => $value) {
            if ($type !== 'default') {
                $hash = Daiquiri_Crypt_Abstract::factory($type)->encrypt($newPassword);

                // update details table in database
                $this->getAdapter()->update('Auth_Details', array(
                    'value' => $hash
                ), array(
                    'user_id = ?' => $id,
                    $this->quoteIdentifier('key') .  '=?' => 'password_' . $type
                ));
            }
        }
    }

    /**
     * Deletes a given user from the database.
     * @param type $id 
     * @throws Exception
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // delete row in user table
        $this->getAdapter()->delete('Auth_User', array('id = ?' => $id));

        // delete rows in details table
        $this->getAdapter()->delete('Auth_Details', array('user_id = ?' => $id));
    }

    /**
     * Fetches the email of all users with the given role as array.
     * @param string $role
     * @return array $rows
     */
    public function fetchEmailByRole($role) {
        if (empty($role)) {
            throw new Exception('$role not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Auth_User', array('email'));
        $select->join('Auth_Roles', '`Auth_Roles`.`id` = `Auth_User`.`role_id`', array());
        $select->where('role = ?', $role);

        $rows = array();
        foreach ($this->fetchAll($select) as $row) {
            $rows[] = $row['email'];
        }
        return $rows;
    }

    /**
     * Fetches the registrations.
     * @return array $rows
     */
    public function fetchRegistrations() {
        $select = $this->select();
        $select->from('Auth_Registration');

        // filter out passwords
        $rows = array();
        foreach ($this->fetchAll($select) as $dbRow) {
            $row = array();
            foreach ($dbRow as $key => $value) {
                if (substr($key, 0, 8) !== 'password' || $key == 'details') {
                    $row[$key] = $value;
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }    

    /**
     * Deletes a singe registation entry.
     * @param type $id 
     * @throws Exception
     */
    public function deleteRegistration($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // delete row in user table
        $this->getAdapter()->delete('Auth_Registration', array('id = ?' => $id));
    }

    /**
     * Registers a new user.
     * @param array $data
     * @return int $id (id of the new entry in the registration table) 
     */
    public function registerUser(array $data) {
        // handle password
        $data['password'] = Daiquiri_Crypt_Abstract::factory()->encrypt($data['newPassword']);

        // handle additional versions of the password
        foreach (array_keys(Daiquiri_Config::getInstance()->auth->password->toArray()) as $type) {
            if ($type != 'default') {
                $data['password_' . $type] = Daiquiri_Crypt_Abstract::factory($type)->encrypt($data['newPassword']);
            }
        }

        // unset password
        unset($data['newPassword']);

        // seperate primary credentials and details and check for password
        $credentials = array();
        $details = array();
        foreach ($data as $key => $value) {
            if (in_array($key, array('username', 'email', 'password', 'code'))) {
                $credentials[$key] = $value;
            } else {
                $details[$key] = $value;
            }
        }

        // construct details string
        $credentials['details'] = Zend_Json::encode($details);

        // insert the new row
        $this->getAdapter()->insert('Auth_Registration', $credentials);

        // return the id of the user just inserted
        return $this->getAdapter()->lastInsertId();
    }

    /**
     * Validate a registred user in the database
     * @param int $id id of the user in the registration table
     * @param string $code
     * @return array $row credntials of the new user 
     */
    public function validateUser($id, $code) {

        // get entry for id
        $select = $this->select();
        $select->from('Auth_Registration');  
        $select->where('id = ?', $id);

        $row = $this->fetchOne($select);

        if ($row) {
            // see if the validation atempt is valid
            if ($row['code'] === $code) {
                // decode the details again
                foreach (Zend_Json::decode($row['details']) as $key => $value) {
                    $row[$key] = $value;
                }

                // get rid of the code, the id, and the details
                unset($row['id']);
                unset($row['code']);
                unset($row['details']);

                // get the users status and role
                $row['status_id'] = Daiquiri_Auth::getInstance()->getStatusId('registered');
                $row['role_id'] = Daiquiri_Auth::getInstance()->getRoleId('user');

                // create user
                $newId = $this->insertRow($row);

                // remove user from registration table
                $this->getAdapter()->delete('Auth_Registration', array('id = ?' => $id));

                // return the user just inserted
                return $this->fetchRow($newId);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
