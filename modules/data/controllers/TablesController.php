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

    public function createAction() {
        // get params
        $redirect = $this -> _getParam('redirect','/data/');
        $databaseId = $this->_getParam('databaseId');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->create($databaseId, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->create($databaseId);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . '/data/tables/create';
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function showAction() {
        // get params
        $redirect = $this -> _getParam('redirect','/data/');
        if ($this->_hasParam('id')) {
            $id = $this->_getParam('id');
            $db = false;
            $table = false;
        } else {
            $id = false;
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
        }
        
        $full = (boolean)(int) $this->_getParam('full');

        $response = $this->_model->show($id, $db, $table, $full);

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function updateAction() {
        // get params
        $redirect = $this -> _getParam('redirect','/data/');
        if ($this->_hasParam('id')) {
            $id = $this->_getParam('id');
            $db = false;
            $table = false;
        } else {
            $id = false;
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
        }

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->update($id, $db, $table, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($id, $db, $table);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . '/data/tables/update';
            if ($id !== false) {
                $action .= '?id=' . $id;
            } else {
                $action .= '?db=' . $db . '&table=' . $table;
            }
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function deleteAction() {
        // get params
        $redirect = $this -> _getParam('redirect','/data/');
        if ($this->_hasParam('id')) {
            $id = $this->_getParam('id');
            $db = false;
            $table = false;
        } else {
            $id = false;
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
        }

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->delete($id, $db, $table, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($id, $db, $table);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . '/data/tables/delete';
            if ($id !== false) {
                $action .= '?id=' . $id;
            } else {
                $action .= '?db=' . $db . '&table=' . $table;
            }
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
