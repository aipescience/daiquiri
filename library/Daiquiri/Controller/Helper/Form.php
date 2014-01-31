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

class Daiquiri_Controller_Helper_Form extends Daiquiri_Controller_Helper_Abstract {

    private $_options = array();

    public function __construct($controller, array $options = array()) {
        parent::__construct($controller);

        // get the module and the controller from the request
        $request = $this->getRequest();
        $module  = $request->module;
        $controller = $request->controller;
        $action = $request->action;

        // construct default options
        $defaults = array(
            'title' => ucfirst($action) . ' ' . substr(str_replace('-',' ', $controller), 0, -1),
            'redirect' => '/' . $module . '/' . $controller . '/'
        );

        $this->_options = array_merge($defaults, $options);
    }

    public function __call($methodname, array $arguments) {
        // get params
        $redirect = $this->getParam('redirect', $this->_options['redirect']);

        // check if POST or GET
        if ($this->getRequest()->isPost()) {
            if ($this->getParam('cancel')) {
                // user clicked cancel
                $this->getController()->redirect($redirect);
            } else {
                // validate form and do stuff
                $response = call_user_func_array(
                    array($this->getModel(),$methodname),
                    array_merge($arguments, array($this->getRequest()->getPost()))
                );
            }
        } else {
            // just display the form
            $response = call_user_func_array(array($this->getModel(),$methodname),$arguments);
        }

        // set action for form
        $this->getController()->setFormAction($response);

        // assign to view
        $response['title'] = $this->_options['title'];
        $this->getController()->setViewElements($response, $redirect);
    }
}