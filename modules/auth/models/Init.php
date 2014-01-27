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

class Auth_Model_Init extends Daiquiri_Model_Init {

    public function parseOptions(array $options) {
        if (!isset($this->_input_options['auth'])) {
            $input = array();
        } else if (!is_array($this->_input_options['auth'])) {
            $this->_error('Auth options need to be an array.');
        } else {
            $input = $this->_input_options['auth'];
        }

        $output = array();

        // construct status array
        $output['status'] = array();
        if ($options['config']['auth']['registration']) {
            if ($options['config']['auth']['confirmation']) {
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
        if ($options['config']['contact']) {
            $output['roles'][] = 'support';
        }
        if ($options['config']['auth']['confirmation']) {
            $output['roles'][] = 'manager';
        }
        $output['roles'][] = 'admin';

        // construct user array
        $output['user'] = array();
        if (isset($input['user'])) {
            if (is_array($input['user'])) {
                $output['user'] = $input['user'];
            } else {
                $this->_error("Auth option 'user' needs to be an array.");
            }
        }

        // construct apps array
        $output['apps'] = array();
        if (isset($input['apps'])) {
            if (is_array($input['apps'])) {
                $output['apps'] = $input['apps'];
            } else {
                $this->_error("Auth option 'apps' needs to be an array.");
            }
        }

        // contstruct resources array
        $output['resources'] = array_merge(array(
            // admin module
            'Admin_IndexController',
            // auth module
            'Auth_Model_Login',
            'Auth_Model_Password',
            'Auth_Model_Registration',
            'Auth_Model_User',
            'Auth_Model_Password',
            'Auth_Model_Account',
            'Auth_Model_Sessions',
            'Auth_Model_Details',
            // config module
            'Config_Model_Entries',
            'Config_Model_Messages',
            'Config_Model_Templates',
            // contact module
            'Contact_Model_Submit',
            'Contact_Model_Messages',
            // data module
            'Data_Model_Viewer',
            'Data_Model_Functions',
            'Data_Model_Databases',
            'Data_Model_Tables',
            'Data_Model_Columns',
            // files module
            'Files_Model_Files',
            // query module
            'Query_Model_Form',
            'Query_Model_Jobs',
            'Query_Model_CurrentJobs',
            'Query_Model_Database',
            'Query_Model_Examples',
            // uws module
            'Query_Model_Uws',
        ), $input['resources']);

        // construct rules array ??
        // $rules = array();
        // if (isset($input['rules'])) {
        //     if (is_array($input['rules'])) {
        //         //$output['rules'] = $input['rules'];
        //     } else {
        //         $this->_error("Auth option 'rules' needs to be an array.");
        //     }
        // }

        // prepare rules array
        $rules = array();
        foreach ($output['roles'] as $role) {
            $rules[$role] = array();
        }

        // construct rules for admin module
        if ($options['config']['contact']) {
            $rules['support']['Admin_IndexController'] = array('index');
        }
        if ($options['config']['contact'] && $options['config']['auth']['confirmation']) {
            $rules['manager']['Admin_IndexController'] = array('index');
        }
        $rules['admin']['Admin_IndexController'] = array('index');

        // construct rules for auth module
        if ($options['config']['auth']) {
            // guest
            $rules['guest']['Auth_Model_Login'] = array('login');
            $rules['guest']['Auth_Model_Password'] = array('forgot','reset');
            if ($options['config']['auth']['registration']) {
                $rules['guest']['Auth_Model_Registration'] = array('register','validate');
            }

            // user
            $rules['user']['Auth_Model_User'] = array('edit');
            $rules['user']['Auth_Model_Password'] = array('change');
            $rules['user']['Auth_Model_Account'] = array('show','update');

            // manager
            if ($options['config']['auth']['confirmation']) {
                $rules['manager']['Auth_Model_User'] = array('rows','cols','show');
                $rules['manager']['Auth_Model_Registration'] = array('confirm','reject');
            }

            // admin
            $rules['admin']['Auth_Model_User'] = array(
                'rows','cols','show','create','update','delete'
            );
            $rules['admin']['Auth_Model_Password'] = array('set');
            $rules['admin']['Auth_Model_Details'] = array('show','create','update', 'delete');
            $rules['admin']['Auth_Model_Sessions'] = array('rows','cols','destroy');
            $rules['admin']['Auth_Model_Registration'] = array('activate','disable','reenable');
        }

        // construct rules for config module
        $rules['admin']['Config_Model_Entries'] = array('index', 'create', 'update', 'delete');
        $rules['admin']['Config_Model_Messages'] = array('index', 'create', 'update', 'delete');
        $rules['admin']['Config_Model_Templates'] = array('index', 'create', 'update', 'delete');
        
        // construct rules for contact module
        if (!empty($options['config']['contact'])) {
            // guest
            $rules['guest']['Contact_Model_Submit'] = array('contact');

            // support
            $rules['support']['Contact_Model_Messages'] = array('rows','cols','show','respond');
        }

        // construct rules for data module
        if (!empty($options['config']['data'])) {
            // guest
            $rules['guest']['Data_Model_Viewer'] = array('rows','cols');

            // admin
            $rules['admin']['Data_Model_Functions'] =array(
                'index','create','show','update','delete'
            );
            $rules['admin']['Data_Model_Databases'] = array(
                'index','create','show','update','delete'
            );
            $rules['admin']['Data_Model_Tables'] = array(
                'create','show','update','delete'
            );
            $rules['admin']['Data_Model_Columns'] = array(
                'create','show','update','delete'
            );
        }

        // construct rules for files module
        if (!empty($options['config']['files'])) {
            $rules['user']['Files_Model_Files'] = array(
                'index', 'single', 'singleSize', 'multi', 'multiSize', 'row', 'rowSize'
            );
        }

        // construct rules for meeting module
        if (!empty($options['config']['meeting'])) {

        }

        // construct rules for query module
        if (!empty($options['config']['query'])) {
            if ($options['config']['query']['guest']) {
                // guest
                $rules['guest']['Query_Model_Form'] = array('submit');
                $rules['guest']['Query_Model_CurrentJobs'] = array(
                    'index', 'show', 'kill', 'remove', 'rename'
                );
                $rules['guest']['Query_Model_Database'] = array(
                    'index', 'download', 'file', 'stream', 'regen'
                );
                $rules['guest']['Query_Model_Examples'] = array('index', 'show');
                if (strtolower($options['config']['query']['processor']['type']) === 'alterplan' ||
                    strtolower($options['config']['query']['processor']['type']) === 'infoplan') {

                    $rules['guest']['Query_Model_Form'][] = 'plan';
                    $rules['guest']['Query_Model_Form'][] = 'mail';
                }
            } else {
                // user
                $rules['user']['Query_Model_Form'] = array('submit');
                $rules['user']['Query_Model_CurrentJobs'] = array(
                    'index', 'show', 'kill', 'remove', 'rename'
                );
                $rules['user']['Query_Model_Database'] = array(
                    'index', 'download', 'file', 'stream', 'regen'
                );
                $rules['user']['Query_Model_Examples'] = array('index', 'show');
                if (strtolower($options['config']['query']['processor']['type']) === 'alterplan' ||
                    strtolower($options['config']['query']['processor']['type']) === 'infoplan') {

                    $rules['user']['Query_Model_Form'][] = 'plan';
                    $rules['user']['Query_Model_Form'][] = 'mail';
                }
            }

            // admin
            $rules['admin']['Query_Model_Jobs'] = array(
                'rows', 'cols', 'show', 'kill', 'remove', 'rename'
            );
            $rules['admin']['Query_Model_Examples'] = array(
                'index', 'show', 'create', 'update', 'delete'
            );
        }

        // construct rules for uws module
        if (!empty($options['config']['uws'])) {
            $rules['user']['Query_Model_Uws'] = array('getJobList', 'getJob', 'getError', 'createPendingJob', 'getQuote','createJobId', 'getPendingJob', 'getQuote', 'setDestructTime','setDestructTimeImpl', 'setExecutionDuration', 'setParameters','deleteJob', 'abortJob', 'runJob');
        }

        $this->_buildRules_r($input['rules'], $output['rules'], $rules);

        $options['auth'] = $output;
        return $options;
    }

    private function _buildRules_r(&$input, &$output, $defaults) {
        if (is_array($defaults)) {
            if (empty($defaults)) {
                $output = array();
            } else if ($input === false) {
                $output = false;
            } else if (is_array($input)) {
                foreach (array_keys($defaults) as $key) {
                    $this->_buildRules_r($input[$key], $output[$key], $defaults[$key]);
                    unset($input[$key]);
                }
            } else {
                $output = $defaults;
            }
            if (!empty($input)) {
                if (is_array($input)) {
                    foreach ($input as $key => $value) {
                        $output[$key] = $value;
                    }
                }
            }
        } else {
            if (isset($input)) {
                if (is_array($input)) {
                    $this->_error("Rules '?' is an array but should not.");
                } else {
                    $output = $input;
                    unset($input);
                }
            } else {
                $output = $defaults;
            }
        }
    }

    public function init(array $options) {
        // create status entries
        $authStatusModel = new Auth_Model_Status();
        if (count($authStatusModel->getValues()) == 0) {
            foreach ($options['auth']['status'] as $status) {
                $a = array('status' => $status);
                $r = $authStatusModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create roles entries
        $authRoleModel = new Auth_Model_Roles();
        if (count($authRoleModel->getValues()) == 0) {
            foreach ($options['auth']['roles'] as $role) {
                $a = array('role' => $role);
                $r = $authRoleModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create users
        Daiquiri_Config::getInstance()->init(); // re-init Configuration object
        $authUserModel = new Auth_Model_User();
        $user = $authUserModel->rows();
        if ($user['nrows'] == 0) {
            foreach ($options['auth']['user'] as $credentials) {
                // get the corresponding role_id and status_id 
                $credentials['role_id'] = $authRoleModel->getId($credentials['role']);
                unset($credentials['role']);
                $credentials['status_id'] = $authStatusModel->getId($credentials['status']);
                unset($credentials['status']);

                // pre-process password first
                $credentials['newPassword'] = $credentials['password'];
                $credentials['confirmPassword'] = $credentials['password'];
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
        Daiquiri_Config::getInstance()->init(); // re-init Configuration object
        $authAppsModel = new Auth_Model_Apps();
        if (count($authAppsModel->getValues()) == 0) {
            foreach ($options['auth']['apps'] as $credentials) {
                // pre-process password first
                $credentials['newPassword'] = $credentials['password'];
                $credentials['confirmPassword'] = $credentials['password'];
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
        if (count($authResourcesModel->getValues()) == 0) {
            foreach ($options['auth']['resources'] as $resource) {
                $a = array(
                    'resource' => $resource,
                );
                $r = $authResourcesModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create acl rules, needs to be after create apps
        $authRulesModel = new Auth_Model_Rules();
        if (count($authRulesModel->getValues()) == 0) {
            foreach ($options['auth']['rules'] as $role => $rule) {
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

