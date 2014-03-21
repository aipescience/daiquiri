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

class Auth_RegistrationController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Registration');
    }

    public function indexAction() {
        $this->getControllerHelper('form')->register();
    }

    public function validateAction() {
        $id = $this->_getParam('id');
        $code = $this->_getParam('code');
        $this->getControllerHelper('form')->validate($id, $code);
    }

    public function confirmAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->confirm($id);
    }

    public function rejectAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->reject($id);
    }

    public function activateAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->activate($id);
    }

    public function disableAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->disable($id);
    }

    public function reenableAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->reenable($id);
    }

}
