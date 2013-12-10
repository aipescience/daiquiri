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
 * Resource class for the user management.
 */
class Auth_Model_Resource_User extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->addTables(array(
            'Auth_Model_DbTable_User',
            'Auth_Model_DbTable_Roles',
            'Auth_Model_DbTable_Status',
            'Auth_Model_DbTable_Details',
            'Auth_Model_DbTable_Registration'
        ));
    }

    /**
     * Returns the colums of (joined) Auth tables.
     * @param string $tableclass
     * @return array 
     */
    public function fetchCols($tableclass = null) {
        $cols = array('id', 'username', 'email', 'role', 'status');
        foreach (Daiquiri_Config::getInstance()->auth->details->toArray() as $detail) {
            $cols[] = $detail;
        }
        return $cols;
    }

    /**
     * Returns the colums as they are in the database.
     * @param string $tableclass
     * @return array 
     */
    public function fetchDbCols($tableclass = null) {
        $cols = $this->fetchCols($tableclass);

        // get the names of the involved tables
        $u = $this->getTable('Auth_Model_DbTable_User')->getName();
        $r = $this->getTable('Auth_Model_DbTable_Roles')->getName();
        $s = $this->getTable('Auth_Model_DbTable_Status')->getName();
        $d = $this->getTable('Auth_Model_DbTable_Details')->getName();

        $dbCols = array();
        foreach ($cols as $col) {
            if (in_array($col, array('id', 'username', 'email'))) {
                $dbCols[$col] = '`' . $u . '`.`' . $col . '`';
            } else if ($col === 'role') {
                $dbCols[$col] = '`' . $r . '`.`' . 'role' . '`';
            } else if ($col === 'status') {
                $dbCols[$col] = '`' . $s . '`.`' . 'status' . '`';
            } else {
                $dbCols[$col] = '`' . $d . '`.`' . $col . '`';
            }
        }

        return $dbCols;
    }

    /**
     * Returns a set of rows from the (joined) Auth tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRows($sqloptions = array()) {
        // get the names of the involved tables
        $u = $this->getTable('Auth_Model_DbTable_User')->getName();
        $r = $this->getTable('Auth_Model_DbTable_Roles')->getName();
        $s = $this->getTable('Auth_Model_DbTable_Status')->getName();
        $d = $this->getTable('Auth_Model_DbTable_Details')->getName();

        // preprocess sqloptions
        $details = array();
        $join = array('status' => false, 'role' => false);
        if (empty($sqloptions['order'])) {
            $sqloptions['order'] = 'id ASC';
        }
        if (empty($sqloptions['from'])) {
            // default columns
            $sqloptions['from'] = array('id', 'username', 'email');
            $join['role'] = true;
            $join['status'] = true;
            $details = Daiquiri_Config::getInstance()->auth->details;
        } else {
            // get rid of password
            foreach ($sqloptions['from'] as $key => $value) {
                // check if its not a column of the user table (except password)
                if (!in_array($value, array('id', 'username', 'role_id', 'status_id', 'email'))) {
                    // decide what to do with the column
                    if ($value === 'role') {
                        $join['role'] = true;
                    } else if ($value === 'status') {
                        $join['status'] = true;
                    } else if (substr($value, 0, 8) === 'password') {
                        unset($sqloptions['from'][$key]);
                    } else {
                        $details[] = $value;
                    }
                    // drop the entry from $sqloptions
                    unset($sqloptions['from'][$key]);
                }
            }
        }

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->setIntegrityCheck(false);

        // add inner joins for the status and the role
        if ($join['role']) {
            $select->join($r, "`$u`.`role_id` = `$r`.`id`", 'role');
        }
        if ($join['status']) {
            $select->join($s, "`$u`.`status_id` = `$s`.`id`", 'status');
        };

        // add an left join for every detail key
        $detailTable = $this->getTable('Auth_Model_DbTable_Details');
        foreach ($details as $key) {
            $detailSelect = $detailTable->select();
            $detailSelect->from($detailTable, array('user_id', 'value'));
            $detailSelect->where('`key` = ?', $key);
            $select->joinLeft(array('tmp_' . $key => $detailSelect), "`$u`.`id` = tmp_" . $key . '.user_id', array($key => 'value'));
        }

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select)->toArray();
        return $rows;
    }

    /**
     * Returns the number of rows from the (joined) Auth tables specified by $where.
     * @param array $where
     * @param string $tableclass
     * @return int 
     */
    public function countRows(array $sqloptions = null, $tableclass = null) {
        //get the table
        $table = $this->getTable('Auth_Model_DbTable_User');

        // create select object
        $select = $table->select();
        $select->setIntegrityCheck(false);
        $select->from($table, 'COUNT(*) as count');

        $join = array('status' => false, 'role' => false);
        if ($sqloptions) {
            if (isset($sqloptions['where'])) {
                foreach ($sqloptions['where'] as $w) {
                    $select = $select->where($w);
                    if (strrpos($w, '`status`') !== False) {
                        $join['status'] = True;
                    } else if (strrpos($w, '`role`') !== False) {
                        $join['role'] = True;
                    }
                }
            }
            if (isset($sqloptions['orWhere'])) {
                foreach ($sqloptions['orWhere'] as $w) {
                    $select = $select->orWhere($w);
                    if (strrpos($w, '`status`') !== False) {
                        $join['status'] = True;
                    } else if (strrpos($w, '`role`') !== False) {
                        $join['role'] = True;
                    }
                }
            }
        }

        // add inner joins for the status and the role
        if ($join['status']) {
            $select->join('Auth_Status', '`Auth_User`.`status_id` = `Auth_Status`.`id`', 'status');
        };
        if ($join['role']) {
            $select->join('Auth_Roles', '`Auth_User`.`role_id` = `Auth_Roles`.`id`', 'role');
        }

        // query database and return
        return (int) $table->fetchRow($select)->count;
    }

    /**
     * Returns a specific row from the (joined) Auth tables.
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRow($id) {
        // get the table
        $table = $this->getTable('Auth_Model_DbTable_User');

        // get the primary sql select object
        $select = $table->select();
        $select->setIntegrityCheck(false);
        $select->from($table, array('id', 'username', 'email', 'role_id', 'status_id'));
        $select->where('Auth_User.id = ?', $id);

        // add inner joins for the status and the role
        $select->join('Auth_Status', 'Auth_User.status_id = Auth_Status.id', 'status');
        $select->join('Auth_Roles', 'Auth_User.role_id = Auth_Roles.id', 'role');

        // get the rowset and return
        $row = $table->fetchAll($select)->current();

        if ($row) {
            $credentials = $row->toArray();

            // get the details table
            $table = $this->getTable('Auth_Model_DbTable_Details');

            // get the sql select object
            $select = $table->select();
            $select->where('user_id = ?', $credentials['id']);
            $details = $table->fetchAll($select)->toArray();

            if ($details) {
                // convert rows to flat array
                foreach ($details as $detail) {
                    if (substr($detail['key'], 0, 9) != 'password_') {
                        $credentials[$detail['key']] = $detail['value'];
                    }
                }
            }

            return $credentials;
        } else {
            throw new Exception('Credentials for id ' . $id . ' not found in db in ' . __METHOD__);
        }
    }

    /**
     * Returns the email the user with a given id.
     * @param int $id
     * @return array
     */
    public function fetchEmail($id) {
        // get row from database
        $result = $this->getTable('Auth_Model_DbTable_User')->find($id);
        return $result->current->email;
    }

    /**
     * Returns the email of all users with a given role.
     * @param string $role
     * @return array
     */
    public function fetchEmailByRole($role) {
        // get the details table
        $table = $this->getTable('Auth_Model_DbTable_User');

        // get rows from database
        $select = $table->select();
        $select->setIntegrityCheck(false);
        $select->from($table, array('id', 'email'));
        $select->where('Auth_Roles.role = ?', $role);
        $select->join('Auth_Roles', 'Auth_User.role_id = Auth_Roles.id', 'role');
        $rows = $table->fetchAll($select)->toArray();

        // convert to flat array and return
        $values = array();
        foreach ($rows as $row) {
            $values[$row['id']] = $row['email'];
        }
        return $values;
    }

    /**
     * Stores a new user in the (joined) Auth tables.
     * @param array $credentials
     * @return int $id (id of the new user) 
     */
    public function storeUser($credentials) {
        // handle unencrypted password
        if (!empty($credentials['newPassword'])) {
            // crypt new password
            $crypt = Daiquiri_Crypt_Abstract::factory();
            $credentials['password'] = $crypt->encrypt($credentials['newPassword']);

            // handle additional versions of the password
            foreach (Daiquiri_Config::getInstance()->auth->password as $key => $value) {
                if ($key != 'default') {
                    $crypt = Daiquiri_Crypt_Abstract::factory($key);
                    $credentials['password_' . $key] = $crypt->encrypt($credentials['newPassword']);
                }
            }
        }
        unset($credentials['newPassword']);

        // seperate primary credentials and details
        foreach ($credentials as $key => $value) {
            if (in_array($key, array('username', 'email', 'password', 'status_id', 'role_id'))) {
                $$key = $value;
            } else {
                $details[$key] = $value;
            }
        }

        // get the user table
        $table = $this->getTable('Auth_Model_DbTable_User');

        // insert the new row
        $table->insert(array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role_id' => $role_id,
            'status_id' => $status_id
        ));

        // get the id of the user just inserted
        $id = $table->getAdapter()->lastInsertId();

        // get the details table
        $table = $this->getTable('Auth_Model_DbTable_Details');

        // store details
        foreach ($details as $key => $value) {
            $table->insert(array(
                'user_id' => $id,
                'key' => $key,
                'value' => $value
            ));
        }

        // create database for user
        if (Daiquiri_Config::getInstance()->query
                && Daiquiri_Config::getInstance()->query->userDb) {
            $userDb = Daiquiri_Config::getInstance()->getUserDbName($username);
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username, '');

            $sql = "CREATE DATABASE `{$userDb}`";
            $adapter->query($sql)->closeCursor();
        }

        // return the id of the newly created user
        return $id;
    }

    /**
     * Updates a user in the (joined) Auth tables.
     * @param int $id
     * @param array $credentials 
     * @throws Exception
     */
    public function updateUser($id, array $credentials) {
        // get the table and the row
        $table = $this->getTable('Auth_Model_DbTable_User');
        $row = $table->find($id)->current();

        if ($row) {
            // update username
            if (array_key_exists('username', $credentials)) {
                $row->username = $credentials['username'];
                unset($credentials['username']);
            }

            // update email
            if (array_key_exists('email', $credentials)) {
                $row->email = $credentials['email'];
                unset($credentials['email']);
            }

            // update role
            if (array_key_exists('role_id', $credentials)) {
                $row->role_id = $credentials['role_id'];
                unset($credentials['role_id']);
            }

            // update role
            if (array_key_exists('status_id', $credentials)) {
                $row->status_id = $credentials['status_id'];
                unset($credentials['status_id']);
            }

            // save row
            $row->save();
        } else {
            throw new Exception('user id "' . $id . '" not found in db in ' . __METHOD__);
        }

        // get the details table
        $table = $this->getTable('Auth_Model_DbTable_Details');

        foreach ($credentials as $key => $value) {
            // get the sql select object
            $select = $table->select();
            $select->where('`user_id` = ?', $id);
            $select->where('`key` = ?', $key);
            $row = $table->fetchAll($select)->current();

            if ($row) {
                // update row
                $row->value = $value;

                // save row
                $row->save();
            } else {
                throw new Exception('key "' . $key . '" not found in db in ' . __METHOD__);
            }
        }
    }

    /**
     * Stores the hashed password of a given user, overwriting an old one.
     * @param int $id
     * @param string $newPassword
     * @throws Exception
     */
    public function storePassword($id, $newPassword) {

        // get the row for the user 
        $table = $this->getTable('Auth_Model_DbTable_User');
        $row = $table->find($id)->current();

        if ($row) {
            // handle main (default) password
            $crypt = Daiquiri_Crypt_Abstract::factory();
            $row->password = $crypt->encrypt($newPassword);
            $row->save();

            // handle additional versions of the password
            $table = $this->getTable('Auth_Model_DbTable_Details');
            foreach (Daiquiri_Config::getInstance()->auth->password as $key => $value) {
                if ($key != 'default') {
                    $select = $table->select();
                    $select->where('`user_id` = ?', $id);
                    $select->where('`key` = ?', 'password_' . $key);
                    $row = $table->fetchAll($select)->current();

                    $crypt = Daiquiri_Crypt_Abstract::factory($key);
                    $row->value = $crypt->encrypt($newPassword);
                    $row->save();
                }
            }
        } else {
            throw new Exception('user id "' . $id . '" not found in db in ' . __METHOD__);
        }
    }

    /**
     * Returns the hashed defauld (or a specific) password of a given user.
     * @param int $id
     * @param string $type
     * @return string 
     */
    public function fetchPassword($id, $type = 'default') {

        if ($type === 'default') {
            // handle main (default) password
            $table = $this->getTable('Auth_Model_DbTable_User');
            $row = $table->find($id)->current();

            if ($row) {
                return $row->password;
            } else {
                throw new Exception('user id "' . $id . '" not found in db in ' . __METHOD__);
            }
        } else {
            // handle additional versions of the password
            $table = $this->getTable('Auth_Model_DbTable_Details');
            $select = $table->select();
            $select->where('`user_id` = ?', $id);
            $select->where('`key` = ?', 'password_' . $type);
            $row = $table->fetchAll($select)->current();

            if ($row) {
                return $row->value;
            } else {
                throw new Exception('password for user id "' . $id . '" not found in db in ' . __METHOD__);
            }
        }
    }

    /**
     * Deletes a given user from the database
     * @param type $id 
     */
    public function deleteUser($id) {
        // get the table and the row
        $table = $this->getTable('Auth_Model_DbTable_User');
        $row = $table->find($id)->current();

        // delete row
        $row->delete();

        // get all detail rows for this user
        $table = $this->getTable('Auth_Model_DbTable_Details');
        $select = $table->select();
        $select->where('`user_id` = ?', $id);
        $rows = $table->fetchAll($select);

        // delete rows
        foreach ($rows as $row) {
            $row->delete();
        }
    }

    /**
     * Registers a new user.
     * @param array $credentials
     * @return int $id (id of the new entry in the registration table) 
     */
    public function registerUser(array $credentials) {
        // handle main (default) password
        $crypt = Daiquiri_Crypt_Abstract::factory();
        $credentials['password'] = $crypt->encrypt($credentials['newPassword']);

        // handle additional versions of the password
        foreach (Daiquiri_Config::getInstance()->auth->password as $key => $value) {
            if ($key != 'default') {
                $crypt = Daiquiri_Crypt_Abstract::factory($key);
                $credentials['password_' . $key] = $crypt->encrypt($credentials['newPassword']);
            }
        }

        // unset new password entry in credential array
        unset($credentials['newPassword']);

        // extract primary credentials and details
        foreach ($credentials as $key => $value) {
            if (in_array($key, array('username', 'email', 'password', 'code',))) {
                $$key = $value;
            } else {
                $details[] = $key . '=' . $value;
            }
        }

        // construct details string
        $detailString = implode('&', $details);

        // get the registration table
        $table = $this->getTable('Auth_Model_DbTable_Registration');

        // insert the new row
        $table->insert(array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'code' => $code,
            'details' => $detailString
        ));

        // return the id of the user just inserted
        return $table->getAdapter()->lastInsertId();
    }

    /**
     * Validate a registred user in the database
     * @param int $id
     * @param string $code
     * @return int $id (id of the new user) 
     */
    public function validateUser($id, $code) {

        $table = $this->getTable('Auth_Model_DbTable_Registration');

        // get entry for id
        $row = $table->find($id)->current();

        if ($row) {
            $user = $row->toArray();

            // see if the validation atempt is valid
            if ($user['code'] === $code) {
                // split the details again
                foreach (explode('&', $user['details']) as $detailPair) {
                    $detail = explode('=', $detailPair);
                    $user[$detail[0]] = $detail[1];
                }

                // get rid of the code, the id, and the details
                unset($user['id']);
                unset($user['code']);
                unset($user['details']);

                // get the users status and role
                $user['status_id'] = Daiquiri_Auth::getInstance()->getStatusId('registered');
                $user['role_id'] = Daiquiri_Auth::getInstance()->getRoleId('user');

                // create user
                $newId = $this->storeUser($user);

                // remove user from registration table
                $row->delete();

                // return the user just inserted
                return $this->fetchRow($newId);
            } else {
                return null;
            }
        }
    }

}
