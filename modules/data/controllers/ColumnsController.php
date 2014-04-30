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

class Data_ColumnsController extends Daiquiri_Controller_Abstract {

    protected $_model;
    
    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Columns');
    }

    public function indexAction() {
        $this->_redirect('/data/');
    }

    public function createAction() {
        $tableId = $this->_getParam('tableId');
        $this->getControllerHelper('form')->create($tableId);
    }

    public function showAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $this->getControllerHelper('form')->show($id);
        } else {
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
            $column = $this->_getParam('column');
            $this->getControllerHelper('form')->show(array('db' => $db, 'table' => $table, 'column' => $column));
        }
    }

    public function updateAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $this->getControllerHelper('form')->update($id);
        } else {
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
            $column = $this->_getParam('column');
            $this->getControllerHelper('form')->update(array('db' => $db, 'table' => $table, 'column' => $column));
        }
    }

    public function deleteAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $this->getControllerHelper('form')->delete($id);
        } else {
            $db = $this->_getParam('db');
            $table = $this->_getParam('table');
            $column = $this->_getParam('column');
            $this->getControllerHelper('form')->delete(array('db' => $db, 'table' => $table, 'column' => $column));
        }
    }

    public function exportAction() {
        $response = $this->_model->export();
        $this->view->data = $response['data'];
        $this->view->status = $response['status'];

        // disable layout
        $this->_helper->layout->disableLayout();
    }
    
}
