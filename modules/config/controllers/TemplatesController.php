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

class Config_TemplatesController extends Daiquiri_Controller_Abstract {

    private $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Config_Model_Templates');
    }

    public function indexAction() {
        $this->view->data = $this->_model->index();
        $this->view->status = 'ok';
    }

    public function createAction() {
        // get redirect url
        $redirect = $this->_getParam('redirect', '/config/templates');

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
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function updateAction() {
        // get redirect url
        $redirect = $this->_getParam('redirect', '/config/templates');
        $template = $this->_getParam('template');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and edit user
                $response = $this->_model->update($template, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($template);
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
        $template = $this->_getParam('template');
        $redirect = $this->_getParam('redirect', '/config/templates');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and delete user
                $response = $this->_model->delete($template, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($template);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
