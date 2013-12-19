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
 * Model for the rule management for acl.
 */
class Auth_Model_Rules extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTable('Auth_Model_DbTable_Rules');
        $this->setValueField('permissions');
    }

    /**
     * Creates a rule for the ACLs.
     * @param string $role
     * @param string $ressource
     * @param string $permissions 
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Rules();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // get additional models
            $roleModel = new Auth_Model_Roles();
            $appsModel = new Auth_Model_Apps();
            $resourceModel = new Auth_Model_Resources();

            // get proper ids
            $roleId = $roleModel->getId($values['role']);
            if ($roleId === null) {
                // check if it is an app
                $roleId = $appsModel->getId($values['role']);
                if ($roleId === null) {
                    return array(
                        'form' => $form,
                        'status' => 'error',
                        'error' => 'role "' . $values['role'] . '" not found in database'
                    );
                } else {
                    $roleId = - $roleId;
                }
            }
            if ($values['resource'] === '') {
                $resourceId = null;
            } else {
                $resourceId = $resourceModel->getId($values['resource']);
                if ($resourceId === null) {
                    return array(
                        'form' => $form,
                        'status' => 'error',
                        'error' => 'resource "' . $values['resource'] . '" not found in database'
                    );
                }
            }
            if ($values['permissions'] === '') {
                $values['permissions'] = null;
            }

            // insert into database
            $this->getResource()->insertRow(array(
                'role_id' => $roleId,
                'resource_id' => $resourceId,
                'permissions' => $values['permissions']
            ));

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
