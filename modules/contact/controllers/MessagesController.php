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

/** This controller manages the contact form, includes ACL now 
 *  @author Kristin
 */
class Contact_MessagesController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Contact_Model_Messages');
    }

    public function indexAction() {
        if (Daiquiri_Auth::getInstance()->checkAcl('Contact_Model_Messages', 'rows')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_AuthError();
        }
    }

    public function colsAction() {
        // call model functions
        $response = $this->_model->cols($this->_request->getQuery());
        
        // assign to view
        $this->view->cols = $response['cols'];
        $this->view->redirect = $this->_getParam('redirect', '/contact/messages/');
        $this->view->status = 'ok';
    }

    public function rowsAction() {
        // call model functions
        $response = $this->_model->rows($this->_request->getQuery());

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
        $this->view->redirect = $this->_getParam('redirect', '/contact/messages/');
        $this->view->status = 'ok';
    }

    public function respondAction() {
        // get paramter from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form
                $response = $this->_model->respond($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->respond($id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
