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

class Data_StaticController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Data_Model_Static');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->view->assign($response);
    }

    public function createAction() {
        $this->getControllerHelper('form')->create();
    }

    public function updateAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('form')->update($id);
    }

    public function deleteAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('form')->delete($id);
    }

    public function exportAction() {
        $response = $this->_model->export();
        $this->view->data = $response['data'];
        $this->view->status = $response['status'];

        // disable layout
        $this->_helper->layout->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }

    public function serveAction() {
        // parse params from route regexp
        $alias = $this->getParam('alias');
        $path = $this->getParam('path');

        // fix trailing slash
        if (empty($path) && !(substr($this->getRequest()->getRequestUri(), -1) === '/')) {
            $this->redirect($this->getRequest()->getRequestUri() . '/');
        }

        // get the file name from th model, or raise an error
        $response = $this->_model->file($alias,$path);

        if ($response['status'] == 'ok') {
            $this->_serve($response['file']);
        } else {
            throw new Exception();
        }
    }

    private function _serve($file) {
        // getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Controller_Front::getInstance()->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // determine mime type of this file
        $finfo = new finfo;
        $mime = $finfo->file($file, FILEINFO_MIME);

        // image or something, deliver!
        header ('X-Sendfile: ' . $file);
        header ('Content-Type: ' . $mime);
        exit;
    }

}
