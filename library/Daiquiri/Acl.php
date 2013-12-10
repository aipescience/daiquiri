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
 * @class   Daiquiri_Acl Acl.php
 * @brief   Daiquiri extensions for the Zend ACL class - handels all ACL stuff
 * 
 * Daiquiri extensions to the Zend ACL class. Hanldes all our roles and rules
 * mechanism. Initialises everything from data saved in the database in close
 * collaboration with the Auth module.
 *
 */
class Daiquiri_Acl extends Zend_Acl {

    private $_defined_resources = array();

    /**
     * @brief   constructor - constructs an Zend_Acl object from information stored in the database
     * 
     * Sets all Roles, Apps, Resources, and Rules from the database as stored in the Auth module. 
     * It always sets up the complete role/rule/ressource stack, so that all ACL information is
     * globally available.
     */
    public function __construct() {

        // get the roles
        $roleModel = new Auth_Model_Roles();
        $roles = $roleModel->getValues();

        // get the apps
        $appsModel = new Auth_Model_Apps();
        $apps = $appsModel->getValues();

        // define roles for acl using roles and apps
        if (!empty($roles)) {
            $this->addRole(new Zend_Acl_Role($roles[1]));
            for ($i = 2; $i <= sizeof($roles); $i++) {
                $this->addRole(new Zend_Acl_Role($roles[$i]), $roles[$i - 1]);
            }
        }
        foreach ($apps as $app) {
            $this->addRole(new Zend_Acl_Role($app));
        }

        // get the resources
        $resourcesModel = new Auth_Model_Resources();
        $this->_defined_resources = $resourcesModel->getValues();

        // define resources
        foreach ($this->_defined_resources as $resource) {
            $this->add(new Zend_Acl_Resource($resource));
        }

        // get the rules
        $rulesModel = new Auth_Model_Rules();
        $rules = $rulesModel->getTable();

        // define permissions
        foreach ($rules as $rule) {

            if ($rule['role_id']) {
                if ($rule['role_id'] > 0) {
                    $role = $roles[$rule['role_id']];
                } else {
                    $role = $apps[- $rule['role_id']];
                }
            } else {
                // null role, i.e. all users
                $role = null;
            }

            if ($rule['resource_id']) {
                $resource = $this->_defined_resources[$rule['resource_id']];
            } else {
                // all resources
                $resource = null;
            }

            if ($rule['permissions']) {
                $permissions = array();
                foreach (explode(',', $rule['permissions']) as $permission) {
                    $array = explode('?', $permission);
                    if (count($array) == 1) {
                        $permissions[] = $permission;
                    } else if (count($array) <= 2) {
                        $permissions[] = $array[0];
                        foreach (explode('&', $array[1]) as $argument) {
                            $permissions[] = $array[0] . '?' . $argument;
                        }
                    } else {
                        throw new Exception('Unable to parse permission string in ' . __METHOD__);
                    }
                }
            } else {
                // all permissions on their resource
                $permissions = null;
            }

            $this->allow($role, $resource, $permissions);
        }
    }

    /**
     * @brief   getResources method - returns an array with all the defined resources.
     * @return  array with all resources
     * 
     * Returns an array with all the resources handeled by this ACL class.
     */
    public function getResources() {
        return $this->_defined_resources;
    }

    /**
     * @brief   hasResource method - checks whether a given resource exists
     * @param   string $resource: resource name
     * @return  TRUE or FALSE
     * 
     * Returns TRUE if the given resource is handled by this ACL instance and FALSE if not.
     */
    public function hasResource($resource) {
        return in_array($resource, $this->_defined_resources);
    }
}
