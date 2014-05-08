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
    private $_csrf = true;

    private $_roles = array();
    private $_status = array();

    /**
     * @brief   constructor - initialises password cryptography and all required database tables
     * 
     * Sets up everything needed for the Zend Authentication mechanism and hooks up the
     * desired password crypto method with the password check. 
     */
    protected function __construct() {
        // get the acl class, this could be more general
        $this->_acl = new Daiquiri_Acl();

        // store roles in auth object
        $roleModel = new Auth_Model_Roles();
        $this->_roles = $roleModel->getResource()->fetchValues('role');

        // store status in auth object
        $statusModel = new Auth_Model_Status();
        $this->_status = $statusModel->getResource()->fetchValues('status');

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
            $activeId = $this->getStatusId('active');
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
    public function authenticateUser($username, $password, $remember = false) {

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

            // get role and status
            $row->status = $this->getStatus($row->status_id);
            $row->role = $this->getRole($row->role_id);

            // get the auth singleton and its storage and store the row
            $storage = Zend_Auth::getInstance()->getStorage();
            $storage->write($row);

            // extend login to two weeks, i.e. 1209600 s
            if ($remember) {
                // extend lifetime of the clients cookie
                Zend_Session::rememberMe(1209600);

                // extent the lifetime of the session in the database
                $saveHandler = Zend_Session::getSaveHandler();
                $saveHandler->setLifetime(1209600, true);
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
    public function authenticateApp($appname, $password) {

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

        // check in the data module first, if metadata exists and handle them
        // accordingly
        $databasesModel = new Data_Model_Databases();
        $response = $databasesModel->show(array('db' => $database));
        if ($response['status'] === 'ok' && $databasesModel->getResource()->checkACL($response['row']['id'], $permission)) {
            if ($table === false) {
                return true;
            } else {
                //access to database granted, so let's check for table access
                $tablesModel = new Data_Model_Tables();
                $response = $tablesModel->show(array('db' => $database, 'table' => $table));

                if ($response['status'] === 'ok' && $tablesModel->getResource()->checkACL($response['row']['id'], $permission)) {
                    return true;
                }
            }
        }

        // scratch database has read access
        $scratchDB = Daiquiri_Config::getInstance()->query->scratchdb;

        if (!empty($scratchDB) && $database === $scratchDB && ($permission === "select" ||
                $permission === "set" )) {
            return true;
        }        

        return false;
    }

    function checkPublicationRoleId($publication_role_id) {
        if ((int) $publication_role_id <= 0) {
            return false;
        }

        $currRole = $this->getCurrentRole();
        $publication_role = $this->getRole($publication_role_id);

        if ($currRole === $publication_role) {
            return true;
        } else {
            return $this->_acl->inheritsRole($currRole, $publication_role);
        }
    }

    /**
     * @brief   isAdmin method - checks if the role of the currently authenticated user is 'admin'
     * @return  bool
     * 
     * Returns if the role of the currently authenticated user is 'admin'.
     */
    public function isAdmin() {
        $role = $this->_getCurrentProperty('role');
        if ($role === 'admin') {
            return true;
        } else {
            return false;
        }
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
     * @brief   getCurrentRoleId method - get current role_id of the user
     * @return  string role
     * 
     * Returns the current roleId of the user.
     */
    public function getCurrentRoleId() {
        $role_id = $this->_getCurrentProperty('role_id');
        if ($role_id) {
            return $role_id;
        } else {
            return 1;
        }
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

    
    /**
     * @brief   unsetCsrf method - unsets the csrf flag of the auth singleton
     * 
     * Unsets the csrf flag of the auth singleton.
     */
    public function unsetCsrf() {
        $this->_csrf = false;
    }
    
    /**
     * @brief   useCsrf method - returns the csrf flag of the auth singleton
     * @return  bool
     * 
     * Returns whether the csrf flag of the auth singleton is set or not. 
     * In this case no CSRF Hashes will be used for forms.
     */
    public function useCsrf() {
        return ($this->_csrf === true);
    }
    
    public function getRoles() {
        return $this->_roles;
    }

    public function getRole($id) {
        if (array_key_exists($id,$this->_roles)) {
            return $this->_roles[$id];
        } else {
           return false;
        }
    }

    public function getRoleId($role) {
        return array_search($role, $this->_roles);
    }

    public function getStatus($id = false) {
        // this is overloaded because of status is a stupid word and to fool the enemy
        if (empty($id)) {
            return $this->_status;
        } else {
            return $this->_status[$id];
        }
    }

    public function getStatusId($status) {
        return array_search($status, $this->_status);
    }

}

