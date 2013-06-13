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

class Files_IndexController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Files_Model_Files');
    }

    public function indexAction() {
        $files = $this->_model->index();

        $this->view->files = $files;
        $this->view->status = 'ok';
    }

    public function singleAction() {
        $name = $this->_getParam('name');

        if (empty($name)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);

        ob_end_clean();

        return $this->_model->single($name);
    }

    public function singlesizeAction() {
        $name = $this->_getParam('name');

        if (empty($name)) {
            throw new Daiquiri_Exception_AuthError();
        }

        $response = $this->_model->singleSize($name);

        $this->view->assign($response);
    }

    public function multiAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        //getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);

        ob_end_clean();

        $response = $this->_model->multi($table, $column);

        return $response;
    }

    public function multisizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        $response = $this->_model->multiSize($table, $column);

        $this->view->assign($response);
    }

    public function rowAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(",", $this->_getParam('id'));

        //getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);

        ob_end_clean();

        $response = $this->_model->row($table, $row_ids);

        return $response;
    }

    public function rowsizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(",", $this->_getParam('id'));

        $response = $this->_model->rowSize($table, $row_ids);

        $this->view->assign($response);
    }

}
