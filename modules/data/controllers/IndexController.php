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

class Data_IndexController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Databases');
    }

    public function indexAction() {
        $databasesModel = Daiquiri_Proxy::factory('Data_Model_Databases');
        $databases = array();
        foreach (array_keys($databasesModel->index()) as $key) {
            $databases[] = $databasesModel->show($key);
        }

        $functionsModel = Daiquiri_Proxy::factory('Data_Model_Functions');
        $functions = array();
        foreach (array_keys($functionsModel->index()) as $key) {
            $functions[] = $functionsModel->show($key);
        }

        $this->view->databases = $databases;
        $this->view->functions = $functions;
        $this->view->status = 'ok';
    }

    public function exportAction() {
        $databases = $this->_model->index();

        $data = array();
        foreach (array_keys($databases) as $key) {
            $data[] = $this->_model->show($key);
        }

        $functions = Daiquiri_Proxy::factory('Data_Model_Functions');
        $func = array();
        foreach (array_keys($functions->index()) as $key) {
            $func[] = $functions->show($key);
        }

        $this->view->data = $data;
        $this->view->func = $func;
        $this->view->status = 'ok';
    }

}
