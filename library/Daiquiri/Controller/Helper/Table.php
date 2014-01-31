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

class Daiquiri_Controller_Helper_Table extends Daiquiri_Controller_Helper_Abstract {

    private $_options = array();

    public function __construct($controller, array $options = array()) {
        parent::__construct($controller);

        // get the module and the controller from the request
        $request = $this->getRequest();
        $module  = $request->module;
        $controller = $request->controller;

        // construct default options
        $defaults = array(
            'base' => '/' . $module . '/' . $controller . '/',
            'model' => $this->getModel()->getClass(),
            'object' => substr(str_replace('-',' ',$controller),0,-1),
            'objects' => str_replace('-',' ',$controller),
            'redirect' => $module
        );

        $this->_options = array_merge($defaults, $options);
    }

    public function index() {
        // get the data from the model
        $response = call_user_func_array(array($this->getModel(),'index'),func_get_args());

        // assign to view
        $response['base'] = $this->_options['base'];
        $response['model'] = $this->getModel()->getClass();
        $response['object'] = $this->_options['object'];
        $response['objects'] = $this->_options['objects'];
        $response['redirect'] = $this->_options['redirect'];
        $this->getController()->setViewElements($response);
    }

    public function show() {
        // get params
        $redirect = $this->getParam('redirect', $this->_options['base']);

        // get the data from the model
        $response = call_user_func_array(array($this->getModel(),'show'),func_get_args());

        // assign to view
        $this->getController()->setViewElements($response, $redirect);
    }
}