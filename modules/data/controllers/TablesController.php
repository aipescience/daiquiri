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

class Data_TablesController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Tables');
    }

    public function indexAction() {
        $this->_redirect('/data/');
    }

    public function showAction() {
        $db = false;
        if($this->_hasParam('id')) {
            $id = $this->_getParam('id');  
        } else {
            $db = $this->_getParam('db');
            $id = $this->_getParam('table');
        }
        
        //dive down recusively?
        $all = true;
        if($this->_hasParam('all')) {
            $a = $this->_getParam('all');

            if($a == 0) {
                $all = false;
            }
        }

        $this->view->data = $this->_model->show($id, $db, $all);

        if($this->view->data === false) {
            $this->view->status = 'error';
        } else {
            $this->view->status = 'ok';
        }
    }

    public function createAction() {
        // get params from request
        $databaseId = $this->_getParam('databaseId');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect('/data/');
            } else {
                // validate form and create new user
                try {
                    $response = $this->_model->create($databaseId, $this->_request->getPost());
                } catch (Exception $e) {
                    $response = array('status' => 'error', 'errors' => $e->getMessage());                    
                }
            }
        } else {
            // just display the form
            $response = $this->_model->create($databaseId);
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function updateAction() {
        // get redirect url and the id
        $id = $this->_getParam('id');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect('/data/');
            } else {
                // validate form and create new user
                $response = $this->_model->update($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($id);
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function deleteAction() {
        // get redirect url and the id
        $id = $this->_getParam('id');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect('/data/');
            } else {
                // validate form and delete user
                $response = $this->_model->delete($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($id);
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
