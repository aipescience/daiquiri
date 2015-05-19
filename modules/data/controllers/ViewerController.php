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

class Data_ViewerController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_db = $this->_getParam('db');
        $this->_table = $this->_getParam('table');
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Viewer');
    }

    public function indexAction() {
        if (empty($this->_db) || empty($this->_table)) {
            $this->view->status = 'error';
        } else {
            $this->view->status = 'ok';
            $this->view->db = $this->_db;
            $this->view->table = $this->_table;
        }
    }

    public function colsAction() {
        $this->getControllerHelper('pagination')->cols();
    }

    public function rowsAction() {
        $this->getControllerHelper('pagination')->rows();
    }

    public function plotAction() {
        $queryParams = $this->getRequest()->getQuery();
        $response = $this->_model->plot($queryParams);
        $this->view->assign($response);
    }
}
