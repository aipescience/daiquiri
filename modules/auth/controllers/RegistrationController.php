<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

class Auth_RegistrationController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Registration');
    }

    public function indexAction() {
        $this->getControllerHelper('form')->index();
    }

    public function deleteAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->delete($id);
    }

    public function registerAction() {
        $this->getControllerHelper('form',array('redirect' => '/'))->register();
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
