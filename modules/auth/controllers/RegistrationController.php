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

class Auth_RegistrationController extends Daiquiri_Controller_Abstract {

    private $_model;

    /**
     * Inititalizes the controller.
     */
    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Registration');
    }

    /**
     * Registers a new user.
     */
    public function indexAction() {
        $redirect = $this->_getParam('redirect', '/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and login
                $response = $this->_model->register($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->register();
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Validates a registered user.
     */
    public function validateAction() {
        $id = $this->_getParam('id');
        $code = $this->_getParam('code');

        $response = $this->_model->validate($id, $code);

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Confirms a Validated user.
     */
    public function confirmAction() {
        // run action on model
        $response = $this->_model->confirm($this->_getParam('id'));

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Reject a registered user.
     */
    public function rejectAction() {
        // run action on model
        $response = $this->_model->reject($this->_getParam('id'));

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Activates a confirmed user.
     */
    public function activateAction() {
        // run action on model
        $response = $this->_model->activate($this->_getParam('id'));

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Disables a user.
     */
    public function disableAction() {
        // run action on model
        $response = $this->_model->disable($this->_getParam('id'));

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    /**
     * Re-enables a disabled user.
     */
    public function reenableAction() {
        // run action on model
        $response = $this->_model->reenable($this->_getParam('id'));

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
