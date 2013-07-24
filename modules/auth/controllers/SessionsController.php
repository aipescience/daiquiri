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

class Auth_SessionsController extends Daiquiri_Controller_Abstract {

    private $_model;

    /**
     * Inititalizes the controller.
     */
    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Sessions');
    }

    /**
     * Displays the user table with a nice js table.
     */
    public function indexAction() {
        if (Daiquiri_Auth::getInstance()->checkAcl('Auth_Model_Sessions', 'rows')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_AuthError();
        }
    }

    /**
     * Displays the cols of the user table.
     */
    public function colsAction() {
        // call model functions
        $response = $this->_model->cols($this->_request->getQuery());
        
        // assign to view
        $this->view->cols = $response['cols'];
        $this->view->redirect = $this->_getParam('redirect', '/auth/sessions/');
        $this->view->status = 'ok';
    }

    /**
     * Displays the rows of the user table.
     */
    public function rowsAction() {
        // call model functions
        $response = $this->_model->rows($this->_request->getQuery());

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
        $this->view->redirect = $this->_getParam('redirect', '/auth/sessions/');
        $this->view->status = 'ok';
    }

    /**
     * Destroys a given session
     */
    public function destroyAction() {
        // get the id of the session to be destroyed
        $id = $this->_getParam('session');

        $response = $this->_model->destroy($id);

        // assign to view        
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
