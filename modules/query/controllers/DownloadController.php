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

class Query_DownloadController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Database');
    }

    public function indexAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');
        $response = $this->_model->download($table, $format);
        $this->view->assign($response);
    }

    public function regenAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');
        $response = $this->_model->regen($table, $format);
        $this->view->assign($response);
    }

    public function fileAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        return $this->_model->file($table, $format);
    }

    public function streamAction() {
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        // Zend stuff is killed inside $model->stream so that errors can still be shown
        $response = $this->_model->stream($table, $format);

        // exit here before zend does something else ...
        if ($response['status'] === "ok") {
            exit();
        } else {
            $this->view->status = $response['status'];
            $this->view->errors = $response['errors'];
        }
    }

}
