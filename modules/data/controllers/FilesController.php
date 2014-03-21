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

class Data_FilesController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Files');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->setViewElements($response);
    }

    public function singleAction() {
        // get parameters from request
        $name = $this->_getParam('name');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // call model function to send the file to the client
        return $this->_model->single($name);
    }

    public function singlesizeAction() {
        // get parameter
        $name = $this->_getParam('name');

        $response = $this->_model->singleSize($name);

        $this->setViewElements($response);
    }

    public function multiAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // call model function to send multible files to the client
        return $this->_model->multi($table, $column);
    }

    public function multisizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        $response = $this->_model->multiSize($table, $column);

        $this->setViewElements($response);
    }

    public function rowAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(',', $this->_getParam('id'));

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        $response = $this->_model->row($table, $row_ids);

        return $response;
    }

    public function rowsizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(',', $this->_getParam('id'));

        $response = $this->_model->rowSize($table, $row_ids);

        $this->setViewElements($response);
    }

}
