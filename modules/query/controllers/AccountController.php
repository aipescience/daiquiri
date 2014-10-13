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

class Query_AccountController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Account');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->view->assign($response);
    }

    public function showJobAction() {
        $id = $this->_getParam('id');
        $response = $this->_model->showJob($id);
        $this->view->assign($response);
    }

    public function renameJobAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form',array('redirect' => '/query'))->renameJob($id);
    }

    public function removeJobAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form',array('redirect' => '/query'))->removeJob($id);
    }

    public function killJobAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form',array('redirect' => '/query'))->killJob($id);
    }

    public function databasesAction() {
        $response = $this->_model->databases();
        $this->view->assign($response);
    }

    public function examplesAction() {
        $response = $this->_model->examples();
        $this->view->assign($response);
    }

    public function functionsAction() {
        $response = $this->_model->functions();
        $this->view->assign($response);
    }

}
