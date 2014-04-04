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

class Query_DownloadController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Database');
    }

    public function indexAction() {
        $table = $this->_getParam('table');
        $this->getControllerHelper('form')->download($table);
    }

    public function regenAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');
        $this->getControllerHelper('form')->regen($table, $format);
    }

    public function fileAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        return $this->_model->file($table, $format);
    }

    public function streamAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        // Zend stuff is killed inside $model->stream so that errors can still be shown
        $response = $this->_model->stream($table, $format);

        // exit here before zend does something else ...
        if ($response['status'] === "ok") {
            exit();
        } else {
            $this->view->status = $response['status'];
            $this->view->error = $response['error'];
        }
    }

}
