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
 * @class   Daiquiri_Auth Auth.php
 * @brief   Daiquiri authentication singleton
 * 
 * Daiquiri authentication singleton that should be used for all
 * Daiquiri authentication needs. Due to being a singleton, this is basically
 * globally available to all parts of the code.
 *
 */
class Daiquiri_Auth extends Daiquiri_Model_Singleton {

    private $_acl;
    private $_userAdapter = null;
    private $_appAdapter = null;

    /**
     * @brief   constructor - initialises password cryptography and all required database tables
     * 
     * Sets up everything needed for the Zend Authentication mechanism and hooks up the
     * desired password crypto method with the password check. 
     */
    protected function __construct() {
        // get the acl class, this could be more general
        $this->_acl = new Daiquiri_Acl();

        // get treatment from default crypt object
        try {
            $crypt = Daiquiri_Crypt_Abstract::factory();
        } catch (Exception $e) {
            $crypt = null;
        }

        if ($crypt !== null) {
            $treatment = $crypt->getTreatment();

            // get treatment for users
            $userTreatment = $treatment;
            $statusModel = new Auth_Model_Status();
            $activeId = $statusModel->getId('active');
            if (is_numeric($activeId)) {
                $userTreatment .= 'AND status_id=' . $activeId;
            }

            // get treatement for apps
            $appTreatment = $treatment . ' AND active=1';

            // set properties of the user adapter
            $this->_userAdapter = new Zend_Auth_Adapter_DbTable();
            $this->_userAdapter->setTableName('Auth_User');
            $this->_userAdapter->setIdentityColumn('username');
            $this->_userAdapter->setCredentialColumn('password');
            $this->_userAdapter->setCredentialTreatment($userTreatment);

            // set properties of the app adapter
            $this->_appAdapter = new Zend_Auth_Adapter_DbTable();
            $this->_appAdapter->setTableName('Auth_Apps');
            $this->_appAdapter->setIdentityColumn('appname');
            $this->_appAdapter->setCredentialColumn('password');
            $this->_appAdapter->setCredentialTreatment($appTreatment);
        }
    }

    /**
     * @brief   authenticateUser method - authenticates a given user with given password
     * @param   string $username: user name
     * @param   string $password: submitted plain text password
     * @return  TRUE or FALSE
     * 
     * Authenticates the given user with the given password and sets the authentication
     * singleton to its new state. Authentication is carried out using HASHing (using given
     * hash) and SALTing.
     */
    public function authenticateUser($username, $password) {

        // first check if username or password are missing
        if (!$username) {
            throw new Exception('Username not given.');
        } else if (!$password) {
            throw new Exception('Password not given.');
        }

        // set username and password
        $this->_userAdapter->setIdentity($username);
        $this->_userAdapter->setCredential($password);

        // check authentification using the adapter
        $result = $this->_userAdapter->authenticate();

        if ($result->isValid()) {

            // store user table row in auth object, but suppress password
            $row = $this->_userAdapter->getResultRowObject(null, 'password');

            // get ip and user agent
            $row->ip = $this->getRemoteAddr();
            $row->userAgent = $this->getUserAgent();

            // get role and status from the corresponding resources
            $statusModel = new Auth_Model_Status();
            $row->status = $statusModel->getValue($row->status_id);
            $roleModel = new Auth_Model_Roles();
            $row->role = $roleModel->getValue($row->role_id);

            // get the auth singleton and its storage and store the row
            $storage = Zend_Auth::getInstance()->getStorage();
            $storage->write($row);

            // get the timeout and use it for the namespace used by Zend_Auth
            $timeout = Daiquiri_Config::getInstance()->auth->timeout;

            if ($timeout) {
                $authns = new Zend_Session_Namespace($storage->getNamespace());
                $authns->setExpirationSeconds($timeout);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @brief   authenticateApp method - authenticates an API access with a given API key
     * @param   string $apikey: API key
     * @return  TRUE or FALSE
     * 
     * Authenticates an API access with the given API key. This mechanism will become deprecated
     * in the near future.
     */
    public function authenticateApp($apikey) {

        // first check if username or password are missing
        if (!$appname) {
            throw new Exception('Appname not given.');
        } else if (!$password) {
            throw new Exception('Password not given.');
        }

        // set username and password
        $this->_appAdapter->setIdentity($appname);
        $this->_appAdapter->setCredential($password);

        // check authentification using the adapter
        $result = $this->_appAdapter->authenticate();

        if ($result->isValid()) {
            // store table row in auth object, but suppress apikey
            $row = $this->_appAdapter->getResultRowObject(null, 'key');
            $row->username = $row->appname;
            $row->role = $row->appname;

            // get ip and user agent
            $row->ip = $this->getRemoteAddr();
            $row->userAgent = $this->getUserAgent();

            // get the auth singleton and its storage and store the row
            $storage = Zend_Auth::getInstance()->getStorage();
            $storage->write($row);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @brief   checkAcl method - checks ACL of a given resource with the 
     *                            authenticated user
     * @param   $resource: the resource that wants access
     * @param   $permission: the desired permissions
     * @param   array $params: an array with a set of given permissions
     * @return  TRUE or FALSE
     * 
     * Checks whether a given resource has given permissions (or set of permissions),
     * using the Daiquiri ACL mechanism. Returns TRUE if ACLs are granted and FALSE if
     * not.
     */
    public function checkAcl($resource, $permission = null, $params = array()) {
        if (Daiquiri_Config::getInstance()->auth == Null) {
            // values are not set in the configuration
            return false;
        } else if (Daiquiri_Config::getInstance()->auth->debug === '1') {
            // switch of security for debugging
            return true;
        }

        // get the current role
        $role = $this->getCurrentRole();

        if (empty($params)) {
            return $this->_acl->isAllowed($role, $resource, $permission);
        } else {
            if ($permission) {
                $specificPermission = $permission;
                $first = true;
                foreach ($params as $key => $value) {
                    $specificPermission .= ':' . $key . '=' . $value;
                    $first = false;
                }
                $bool = $this->_acl->isAllowed($role, $resource, $specificPermission);
                return $bool;
            } else {
                throw new Exception('Arguments only possible with specified permission.');
            }
        }
    }

    /**
     * @brief   checkMethod method - checks whether a given method in a given class 
     *                               can be accessed or not
     * @param   $class: the name of the class
     * @param   $method: the name of the method
     * @return  TRUE or FALSE
     * 
     * Checks whether a given method in a given class can be accessed by the user. If the
     * Config->auth->debug flag is set to one, this function will allways grant acces.
     */
    public function checkMethod($class, $method) {
        // switch of security for debugging
        if (Daiquiri_Config::getInstance()->auth->debug === '1') {
            return true;
        }

        // get the current role
        $role = $this->getCurrentRole();

        return $this->_acl->isAllowed($role, $class, $method);
    }

    /**
     * @brief   checkDbTable method - checks whether user has access to a given database
     *                                and table
     * @param   $database: database name
     * @param   $table: table name
     * @param   $permission: the desired permission
     * @return  TRUE or FALSE
     * 
     * Checks whether the user has access to the given database and table with the desired
     * permission. This uses the Data module for ACLing of the databases and tables. The information
     * stored in the database meta data store is needed for this. 
     *
     * BACKWARD COMPATIBILITY: DEPRECATED: For backward compatibility, ACLs can also be specified
     * in the config file as rules. However since this functionality only remains for backward
     * compatibility with old development versions. Expect this to be removed soon.
     */
    public function checkDbTable($database, $table, $permission) {
        // switch of security for debugging
        if (Daiquiri_Config::getInstance()->auth->debug === '1') {
            return true;
        }

        // get the current role
        $role = $this->getCurrentRole();

        $userDB = Daiquiri_Config::getInstance()->getUserDbName($this->getCurrentUsername());
        if ($database === $userDB) {
            return true;
        }

        //check in the data module first, if metadata exists and handle them
        //accordingly
        $metaDb = new Data_Model_Databases();

        $dbId = $metaDb->fetchIdWithName($database);

        if ($dbId !== false && $metaDb->checkACL($dbId, $permission)) {
            //access to database granted, so let's check for table access
            $metaTable = new Data_Model_Tables();

            $tableId = $metaTable->fetchIdWithName($dbId, $table);

            if ($tableId !== false && $metaTable->checkACL($tableId, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @brief   getCurrentUsername method - get user name of currently authenticated user
     * @return  string user name
     * 
     * Returns the user name of the currently authenticated user.
     */
    public function getCurrentUsername() {
        $identity = $this->_getCurrentProperty('username');
        if ($identity) {
            return $identity;
        } else {
            return 'guest';
        }
    }

    /**
     * @brief   getCurrentUsername method - get row ID of currently authenticated user
     * @return  string user id - "-1" if guest user
     * 
     * Returns the row ID of currently authenticated user. If this is a guest user, this
     * function returns "-1".
     *
     * WARNING: User ID "-1" should never be given to a valid user!
     */
    public function getCurrentId() {
        $id = $this->_getCurrentProperty('id');

        if ($id) {
            return $this->_getCurrentProperty('id');
        } else {
            //this is a guest user...
            return "-1";
        }
    }

    /**
     * @brief   getCurrentRole method - get current role of the user
     * @return  string role
     * 
     * Returns the current role of the user.
     */
    public function getCurrentRole() {
        $role = $this->_getCurrentProperty('role');
        if ($role) {
            return $role;
        } else {
            return 'guest';
        }
    }

    /**
     * @brief   getCurrentRoleParents method - get all parents of the current user role
     * @return  array parent roles
     * 
     * Returns all parents of the current user role.
     */
    public function getCurrentRoleParents() {
        $role = $this->getCurrentRole();

        $parents = $this->_acl->getParentsForRole($role);

        if (empty($parents)) {
            $parents[] = "guest";
        }

        return $parents;
    }

    /**
     * @brief   getCurrentEmail method - get current email address of user
     * @return  string email address
     * 
     * Returns the current email address of user.
     */
    public function getCurrentEmail() {
        return $this->_getCurrentProperty('email');
    }

    /**
     * @brief   getRemoteAddr method - Returns the remote ip address
     * @return  string IP address
     * 
     * Returns the remote ip address
     */
    public function getRemoteAddr() {
        return Zend_Controller_Front::getInstance()->getRequest()->getServer('REMOTE_ADDR');
    }

    /**
     * @brief   getUserAgent method - Returns the remote users agent
     * @return  string USER agent
     * 
     * Returns the remote users agent
     */
    public function getUserAgent() {
        return Zend_Controller_Front::getInstance()->getRequest()->getServer('HTTP_USER_AGENT');
    }

    /**
     * @brief   _getCurrentProperty method - return the current properties of this user
     * @return  $property
     * 
     * Returns the current properties of this user.
     */
    private function _getCurrentProperty($property) {
        $auth = Zend_Auth::getInstance();


        if (php_sapi_name() !== 'cli' && $auth->hasIdentity()) {
            return $auth->getIdentity()->$property;
        } else {
            return null;
        }
    }

}