<?php
/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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