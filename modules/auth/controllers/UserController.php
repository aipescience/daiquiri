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

class Auth_UserController extends Daiquiri_Controller_Abstract {

    private $_model;

    /**
     * Inititalizes the controller.
     */
    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_User');
    }

    /**
     * Displays the user table with a nice js table.
     */
    public function indexAction() {
        if (Daiquiri_Auth::getInstance()->checkAcl('Auth_Model_User', 'rows')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_AuthError();
        }
    }

    /**
     * Displays the cols of the user table.
     */
    public function colsAction() {
        // get parameters from request
        $redirect = $this->_getParam('redirect', '/auth/user/');
        $params = $this->_getTableParams();

        $this->view->redirect = $redirect;
        $this->view->data = $this->_model->cols($params);
        $this->view->status = 'ok';
    }

    /**
     * Displays the rows of the user table.
     */
    public function rowsAction() {
        // get parameters from request
        $redirect = $this->_getParam('redirect', '/auth/user/');
        $params = $this->_getTableParams();

        // call model functions
        $this->view->redirect = $redirect;
        $this->view->data = $this->_model->rows($params);
        $this->view->status = 'ok';
    }

    /**
     * Shows credentials for a given user.
     */
    public function showAction() {
        // get params from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/auth/user/');

        // call model method
        $this->view->redirect = $redirect;
        $this->view->data = $this->_model->show($id);
        $this->view->status = 'ok';
    }

    /**
     * Creates a new user.
     */
    public function createAction() {
        $redirect = $this->_getParam('redirect', '/auth/user/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and create new user
                $response = $this->_model->create($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->create();
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Edits an existing user in the database.
     */
    public function updateAction() {
        // get the id of the user to be edited
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/auth/user/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and edit user
                $response = $this->_model->update($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Edits an the user which is currently logged in.
     * Uses different form as updateAction (without status and role).
     */
    public function editAction() {
        // get redirect url
        $redirect = $this->_getParam('redirect', '/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and edit user
                $response = $this->_model->edit($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->edit();
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Deletes a user.
     */
    public function deleteAction() {
        // get the id of the user to be deleted
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/auth/user/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and delete user
                $response = $this->_model->delete($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
