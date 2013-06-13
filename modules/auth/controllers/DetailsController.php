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

class Auth_DetailsController extends Daiquiri_Controller_Abstract {

    private $_model;

    /**
     * Inititalizes the controller.
     */
    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Details');
    }

    public function showAction() {
        // get parameter
        $id = $this->_getParam('id');
        $key = $this->_getParam('key');

        $response = $this->_model->show($id, $key);
        $this->view->data = $response['data'];
        $this->view->status = $response['status'];
    }

    public function createAction() {
        // get redirect url
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/auth/user/show/id/' . $id);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and create new user
                $response = $this->_model->create($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->create($id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function updateAction() {
        // get parameter
        $id = $this->_getParam('id');
        $key = $this->_getParam('key');
        $redirect = $this->_getParam('redirect', '/auth/user/show/id/' . $id);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and create new user
                $response = $this->_model->update($id, $key, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($id, $key);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function deleteAction() {
        // get parameter
        $id = $this->_getParam('id');
        $key = $this->_getParam('key');
        $redirect = $this->_getParam('redirect', '/auth/user/show/id/' . $id);


        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and delete user
                $response = $this->_model->delete($id, $key, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($id, $key);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
