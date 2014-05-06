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

class Auth_PasswordController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Auth_Model_Password');
    }

    public function showAction() {
        $id = $this->_getParam('id');
        $type = $this->_getParam('type', 'default');
        $this->getControllerHelper('table')->show($id, $type);
    }

    public function changeAction() {
        $this->getControllerHelper('form')->change();
    }

    public function forgotAction() {
       $this->getControllerHelper('form')->forgot();
    }

    public function resetAction() {
        $id = $this->_getParam('id');
        $code = $this->_getParam('code');
        $this->getControllerHelper('form')->reset($id, $code);
    }

    public function setAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->set($id);
    }

}
