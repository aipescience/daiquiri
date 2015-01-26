<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Data_DatabasesController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Databases');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->view->assign($response);
    }

    public function createAction() {
        set_time_limit(600);
        $this->getControllerHelper('form')->create();
    }

    public function showAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $response = $this->_model->show($id);
        } else {
            $db = $this->_getParam('db');
            $response = $this->_model->show(array('db' => $db));
        }
        $this->view->assign($response);
    }

    public function updateAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $this->getControllerHelper('form')->update($id);
        } else {
            $db = $this->_getParam('db');
            $this->getControllerHelper('form')->update(array('db' => $db));
        }
    }

    public function deleteAction() {
        if ($this->_hasParam('id')) {
            $id = (int) $this->_getParam('id');
            $this->getControllerHelper('form')->delete($id);
        } else {
            $db = $this->_getParam('db');
            $this->getControllerHelper('form')->delete(array('db' => $db));
        }
    }

    public function exportAction() {
        $response = $this->_model->export();
        $this->view->data = $response['data'];
        $this->view->status = $response['status'];

        // disable layout
        $this->_helper->layout->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }

}
