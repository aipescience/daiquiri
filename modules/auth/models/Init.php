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

class Auth_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the auth module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Auth_Model_Login',
            'Auth_Model_Password',
            'Auth_Model_Registration',
            'Auth_Model_User',
            'Auth_Model_Account',
            'Auth_Model_Sessions',
            'Auth_Model_Details',
            'Auth_Model_DetailKeys'
        );
    }

    /**
     * Returns the acl rules for the auth module.
     * @return array $rules
     */
    public function getRules() {
        $rules = array();

        // guest
        $rules['guest'] = array(
            'Auth_Model_Login' => array('login'),
            'Auth_Model_Password' => array('forgot','reset')
        );
        if ($this->_init->options['config']['auth']['registration']) {
            $rules['guest']['Auth_Model_Registration'] = array('register','validate');
        }

        // user
        $rules['user'] = array(
            'Auth_Model_User' => array('edit'),
            'Auth_Model_Password' => array('change'),
            'Auth_Model_Account' => array('show','update')
        );

        // manager
        if ($this->_init->options['config']['auth']['confirmation']) {
            $rules['manager'] = array(
                'Auth_Model_User' => array('rows','cols','show'),
                'Auth_Model_Registration' => array('confirm','reject')
            );
        }

        // admin
        $rules['admin'] = array(
            'Auth_Model_User' => array('rows','cols','show','create','update','delete','export'),
            'Auth_Model_Password' => array('set'),
            'Auth_Model_Details' => array('show','create','update','delete'),
            'Auth_Model_DetailKeys' => array('index','show','create','update','delete'),
            'Auth_Model_Sessions' => array('rows','cols','destroy'),
            'Auth_Model_Registration' => array('index','delete','activate','disable','reenable')
        );

        return $rules;
    }

    /**
     * Processes the 'auth' part of $options['config'].
     */
    public function processConfig() {
        if (!isset($this->_init->input['config']['auth'])) {
            $input = array();
        } else if (!is_array($this->_init->input['config']['auth'])) {
            $this->_error('Auth config options need to be an array.');
        } else {
            $input = $this->_init->input['config']['auth'];
        }

        // create default entries
        $defaults = array(
            'registration' => false,
            'activation' => false,
            'confirmation' => false,
            'password' => array(
                'default' => array(
                    'algo' => 'cryptSha512'
                )
            ),
            'timeout' => 0,
            'mailOnChangePassword' => false,
            'mailOnUpdateUser' => false,
            'changeUsername' => false,
            'changeEmail' => true,
            'lowerCaseUsernames' => false,
            'usernameMinLength' => 4,
            'passwordMinLength' => 4
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // process and check
        if (!empty($output['confirmation'])) {
            $output['registration'] = true;
            $output['activation'] = true;
        }
        if (!empty($output['activation'])) {
            $output['registration'] = true;
        }

        // set options
        $this->_init->options['config']['auth'] = $output;
    }

    /**
     * Processes the 'auth' part of $options['init'].
     */
    public function processInit() {
        if (!isset($this->_init->input['init']['auth'])) {
            $input = array();
        } else if (!is_array($this->_init->input['init']['auth'])) {
            $this->_error('Auth init options need to be an array.');
        } else {
            $input = $this->_init->input['init']['auth'];
        }

        // init output array
        $output = array();

        // construct status array
        $output['status'] = array();
        if ($this->_init->options['config']['auth']['registration']) {
            if ($this->_init->options['config']['auth']['confirmation']) {
                $output['status'] = array('active', 'registered', 'confirmed', 'disabled');
            } else {
                $output['status'] = array('active', 'registered', 'disabled');
            }
        } else {
            $output['status'] = array('active', 'disabled');
        }

        // construct roles array
        $output['roles'] = array('guest', 'user');
        if (isset($input['roles'])) {
            foreach ($input['roles'] as $role) {
                if (! in_array($role, array('guest','user','support','manager','admin'))) {
                    $output['roles'][] = $role;
                }
            }
        }
        if (in_array('contact',$this->_init->options['modules'])) {
            $output['roles'][] = 'support';
        }
        if ($this->_init->options['config']['auth']['confirmation'] 
            || in_array('meetings',$this->_init->options['modules'])) {
            $output['roles'][] = 'manager';
        }
        $output['roles'][] = 'admin';

        // construct detail keys array
        $output['detailKeys'] = array();
        if (isset($input['detailKeys'])) {
            if (is_array($input['detailKeys'])) {
                $output['detailKeys'] = $input['detailKeys'];
            } else {
                $this->_error("Auth init option 'detailKeys' needs to be an array.");
            }
        } else {
            $output['detailKeys'] = array(
                array('key' => 'firstname'),
                array('key' => 'lastname')
            );
        }

        // construct user array
        $output['user'] = array();
        if (isset($input['user'])) {
            if (is_array($input['user'])) {
                $output['user'] = $input['user'];
            } else {
                $this->_error("Auth init option 'user' needs to be an array.");
            }
        }

        // construct apps array
        $output['apps'] = array();
        if (isset($input['apps'])) {
            if (is_array($input['apps'])) {
                $output['apps'] = $input['apps'];
            } else {
                $this->_error("Auth init option 'apps' needs to be an array.");
            }
        }

        // prepare resources array
        $output['resources'] = array();

        // fetch resources from ALL modules
        foreach ($this->_init->models as $model) {
            foreach ($model->getResources() as $resource) {
                $output['resources'][] = $resource;
            }
        }

        // fetch resources specified in the init.php file
        if (isset($input['resources'])) {
            if (is_array($input['resources'])) {
                $output['resources'] = array_merge($output['resources'], $input['resources']);
            } else {
                $this->_error("Auth option 'resources' needs to be an array.");
            }
        }

        // prepare rules array
        $output['rules'] = array();
        foreach ($output['roles'] as $role) {
            $output['rules'][$role] = array();
        }

        // get rules for the ACTIVE modules
        foreach ($this->_init->options['modules'] as $module) {
            $model = $this->_init->models[$module];
            foreach ($model->getRules() as $role => $rules) {
                if (!isset($output['rules'][$role])) {
                    $output['rules'][$role] = array();
                }
                foreach ($rules as $resource => $permissions) {
                    $output['rules'][$role][$resource] = $permissions;
                }
            }
        }

        // add rules from the input
        if (isset($input['rules'])) {
            foreach ($input['rules'] as $role => $rules) {
                if (!isset($output['rules'][$role])) {
                    $output['rules'][$role] = array();
                }
                foreach ($rules as $resource => $permissions) {
                    $output['rules'][$role][$resource] = $permissions;
                }
            }
        }

        $this->_init->options['init']['auth'] = $output;
    }

    /**
     * Initializes the database with the init data for the meetings module.
     */
    public function init() {
        // create status entries
        $authStatusModel = new Auth_Model_Status();
        if ($authStatusModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['status'] as $status) {
                $a = array('status' => $status);
                $r = $authStatusModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create roles entries
        $authRoleModel = new Auth_Model_Roles();
        if ($authRoleModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['roles'] as $role) {
                $a = array('role' => $role);
                $r = $authRoleModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create detail keys entries
        $authDetailKeysModel = new Auth_Model_DetailKeys();
        if ($authDetailKeysModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['detailKeys'] as $a) {
                if (!isset($a['type_id'])) {
                    $a['type_id'] = 0;
                }
                $r = $authDetailKeysModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create users
        $authUserModel = new Auth_Model_User();
        if ($authUserModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['user'] as $credentials) {
                // get the corresponding role_id and status_id 
                $credentials['role_id'] = Daiquiri_Auth::getInstance()->getRoleId($credentials['role']);
                unset($credentials['role']);
                $credentials['status_id'] = Daiquiri_Auth::getInstance()->getStatusId($credentials['status']);
                unset($credentials['status']);

                // pre-process password first
                $credentials['new_password'] = $credentials['password'];
                $credentials['confirm_password'] = $credentials['password'];
                unset($credentials['password']);

                // fake request parametes to make 
                Zend_Controller_Front::getInstance()->getRequest()->setParams($credentials);
                
                // create user
                $r = $authUserModel->create($credentials);

                // clean up request
                Zend_Controller_Front::getInstance()->getRequest()->setParams(array());

                $this->_check($r, $credentials);
            }
        }

        // create apps
        $authAppsModel = new Auth_Model_Apps();
        if ($authAppsModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['apps'] as $credentials) {
                // pre-process password first
                $credentials['new_password'] = $credentials['password'];
                $credentials['confirm_password'] = $credentials['password'];
                unset($credentials['password']);

                // fake request parametes to make 
                Zend_Controller_Front::getInstance()->getRequest()->setParams($credentials);

                // create user
                $r = $authAppsModel->create($credentials);

                // clean up request
                Zend_Controller_Front::getInstance()->getRequest()->setParams(array());

                $this->_check($r, $credentials);
            }
        }

        // create acl ressources
        $authResourcesModel = new Auth_Model_Resources();
        if ($authResourcesModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['resources'] as $resource) {
                $a = array(
                    'resource' => $resource,
                );
                $r = $authResourcesModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create acl rules, needs to be after create apps
        $authRulesModel = new Auth_Model_Rules();
        if ($authRulesModel->getResource()->countRows() === 0) {
            foreach ($this->_init->options['init']['auth']['rules'] as $role => $rule) {
                foreach ($rule as $resource => $permissions) {
                    $a = array(
                        'role' => $role,
                        'resource' => $resource,
                        'permissions' => implode(',', $permissions)
                    );
                    $r = $authRulesModel->create($a);
                    $this->_check($r, $a);
                }
            }
        }
    }

}

