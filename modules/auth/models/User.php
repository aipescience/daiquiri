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
 * Model for the user management.
 */
class Auth_Model_User extends Daiquiri_Model_PaginatedTable {

    /**
     * Possible options for each user.
     * @var array 
     */
    private $_options = array(
        'Show' => array(
            'url' => '/auth/user/show',
            'class' => 'daiquiri-user-show',
            'permission' => 'show',
            'resource' => 'Auth_Model_User'
        ),
        'Update' => array(
            'url' => '/auth/user/update',
            'class' => 'daiquiri-user-update',
            'permission' => 'update',
            'resource' => 'Auth_Model_User'
        ),
        'Delete' => array(
            'url' => '/auth/user/delete',
            'class' => 'daiquiri-user-delete',
            'permission' => 'delete',
            'resource' => 'Auth_Model_User'
        ),
        'Confirm' => array(
            'url' => '/auth/registration/confirm',
            'class' => 'daiquiri-user-confirm',
            'permission' => 'confirm',
            'resource' => 'Auth_Model_Registration',
            'prerequisites' => array('registered')
        ),
        'Reject' => array(
            'url' => '/auth/registration/reject',
            'class' => 'daiquiri-user-reject',
            'permission' => 'reject',
            'resource' => 'Auth_Model_Registration',
            'prerequisites' => array('registered')
        ),
        'Activate' => array(
            'url' => '/auth/registration/activate',
            'class' => 'daiquiri-user-activate',
            'permission' => 'activate',
            'resource' => 'Auth_Model_Registration',
            'prerequisites' => array('registered', 'confirmed')
        ),
        'Disable' => array(
            'url' => '/auth/registration/disable',
            'class' => 'daiquiri-user-disable',
            'permission' => 'disable',
            'resource' => 'Auth_Model_Registration',
            'prerequisites' => array('active')
        ),
        'Reenable' => array(
            'url' => '/auth/registration/reenable',
            'class' => 'daiquiri-user-reenable',
            'permission' => 'reenable',
            'resource' => 'Auth_Model_Registration',
            'prerequisites' => array('disabled')
        ),
        'Password' => array(
            'url' => '/auth/password/set',
            'class' => 'daiquiri-user-password',
            'permission' => 'set',
            'resource' => 'Auth_Model_Password'
         )
    );

    /**
     * Default columns to be returned in cols/rows.
     * @var array 
     */
    private $_cols = array('id', 'username', 'email', 'role', 'status');

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Returns the columns for the index.
     * @return array 
     */
    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        $cols = array();
        foreach ($params['cols'] as $name) {
            $col = array('name' => $name);
            if ($name === 'id') {
                $col['width'] = '3em';
                $col['align'] = 'center';
            } else if ($name === 'username') {
                $col['width'] = '8em';
            } else if ($name === 'email') {
                $col['width'] = '16em';
            } else if ($name === 'role') {
                $col['width'] = '6em';
            } else if ($name === 'status') {
                $col['width'] = '6em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }

        if (isset($params['options']) && $params['options'] === 'true') {
            $cols[] = array(
                'name' => 'options',
                'width' => '30em',
                'sortable' => 'false'
            );
        }

        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Returns the main data of the user table.
     * @return array 
     */
    public function rows(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);
        
        // loop through the table and add options
        if (isset($params['options']) && $params['options'] === 'true') {
            for ($i = 0; $i < count($rows); $i++) {
                $id = $rows[$i]['id'];
                $links = '';

                $status = null;
                if (array_search('status', $params['cols'])) {
                    $status = $rows[$i]['status'];
                }

                foreach ($this->_options as $key => $value) {
                    if ($status !== null &&
                            isset($value['prerequisites']) &&
                            !in_array($status, $value['prerequisites'])) {
                        // pass
                    } else {
                        $links .= $this->internalLink(array(
                            'text' => $key,
                            'href' => $value['url'] . '/id/' . $id,
                            'resource' => $value['resource'],
                            'permission' => $value['permission'],
                            'class' => $value['class'],
                            'append' => '&nbsp;'));
                    }
                }

                $rows[$i]['options'] = $links;
            }
        }

        return $this->_response($rows, $sqloptions);
    }

    /**
     * Returns the credentials of a given user from the database.
     * @return array 
     */
    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Creates a new user.
     * @param array $formParams
     * @return Object
     */
    public function create(array $formParams = array()) {
        // get the status model, the roles model and the roles
        $status = Daiquiri_Auth::getInstance()->getStatus();
        $roles = Daiquiri_Auth::getInstance()->getRoles();
        unset($roles[1]); // unset the guest user

        // create the form object
        $form = new Auth_Form_Create(array(
                    'details' => Daiquiri_Config::getInstance()->auth->details->toArray(),
                    'status' => $status,
                    'roles' => $roles
                ));

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // unset some elements
                unset($values['confirmPassword']);

                // create the user
                $id = $this->getResource()->storeUser($values);

                // log the event
                $detailsResource = new Auth_Model_Resource_Details();
                $detailsResource->logEvent($id, 'create');

                return array('status' => 'ok');
            } else {
                return array('form' => $form, 'status' => 'validation failed');
            }
        }
        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an existing user.
     * @param int $id
     * @param array $formParams
     * @return Object
     */
    public function update($id, array $formParams = array()) {
        // get the status model, the roles model and the roles
        $status = Daiquiri_Auth::getInstance()->getStatus();
        $roles = Daiquiri_Auth::getInstance()->getRoles();
        unset($roles[1]); // unset the guest user

        // create the form object
        $form = new Auth_Form_Update(array(
                    'user' => $this->getResource()->fetchRow($id),
                    'details' => Daiquiri_Config::getInstance()->auth->details->toArray(),
                    'status' => $status,
                    'roles' => $roles,
                    'changeUsername' => Daiquiri_Config::getInstance()->auth->changeUsername,
                    'changeEmail' => Daiquiri_Config::getInstance()->auth->changeEmail,
                ));

        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // update the user and redirect
            $this->getResource()->updateUser($id, $values);

            // log the event
            $detailsResource = new Auth_Model_Resource_Details();
            $detailsResource->logEvent($id, 'update');

            return array('status' => 'ok');
        } else {
            $csrf = $form->getElement('csrf');
            $csrf->initCsrfToken();
            return array(
                'form' => $form,
                'csrf' => $csrf->getHash(),
                'status' => 'error',
                'errors' => $form->getMessages()
                );
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Edits the credentials of the currently logged in user.
     * @param array $formParams
     * @return array
     */
    public function edit(array $formParams = array()) {
        // get id
        $id = Daiquiri_Auth::getInstance()->getCurrentId();

        // create the form object
        $form = new Auth_Form_Edit(array(
                    'user' => $this->getResource()->fetchRow($id),
                    'details' => Daiquiri_Config::getInstance()->auth->details->toArray(),
                    'changeUsername' => Daiquiri_Config::getInstance()->auth->changeUsername,
                    'changeEmail' => Daiquiri_Config::getInstance()->auth->changeEmail,
                ));

        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // update the user and redirect
            $this->getResource()->updateUser($id, $values);

            // log the event
            $detailsResource = new Auth_Model_Resource_Details();
            $detailsResource->logEvent($id, 'edit');

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes an existing user.
     * @param int $id
     * @param array $formParams
     * @return array 
     */
    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Delete();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // delete the user and redirect
                $this->getResource()->deleteUser($id);

                // invalidate the session of the user
                $resource = new Auth_Model_Resource_Sessions();
                foreach ($resource->fetchAuthSessionsByUserId($id) as $session) {
                    $resource->deleteRow($session);
                };
                
                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
