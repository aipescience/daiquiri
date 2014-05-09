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

class Data_FilesController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Files');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->view->assign($response);
    }

    public function singleAction() {
        // get parameters from request
        $name = $this->_getParam('name');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // call model function to send the file to the client
        return $this->_model->single($name);
    }

    public function singlesizeAction() {
        // get parameter
        $name = $this->_getParam('name');

        $response = $this->_model->singleSize($name);

        $this->view->assign($response);
    }

    public function multiAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // call model function to send multible files to the client
        return $this->_model->multi($table, $column);
    }

    public function multisizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $column = $this->_getParam('column');

        $response = $this->_model->multiSize($table, $column);

        $this->view->assign($response);
    }

    public function rowAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(',', $this->_getParam('id'));

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        $response = $this->_model->row($table, $row_ids);

        return $response;
    }

    public function rowsizeAction() {
        // get parameters from request
        $table = $this->_getParam('table');
        $row_ids = explode(',', $this->_getParam('id'));

        $response = $this->_model->rowSize($table, $row_ids);

        $this->view->assign($response);
    }

}
