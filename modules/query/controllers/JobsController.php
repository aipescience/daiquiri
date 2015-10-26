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

class Query_JobsController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Jobs');
    }

    public function indexAction() {
        if (Daiquiri_Auth::getInstance()->checkAcl('Query_Model_Jobs', 'rows')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_Unauthorized();
        }
    }

    public function colsAction() {
        $this->getControllerHelper('pagination')->cols();
    }

    public function rowsAction() {
        $this->getControllerHelper('pagination')->rows();
    }

    public function showAction() {
        $id = $this->_getParam('id');
        $response = $this->_model->show($id);
        $this->view->assign($response);
    }

    public function killAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->kill($id);
    }

    public function removeAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->remove($id);
    }

    public function exportAction() {
        // get params
        $redirect = $this->getParam('redirect', '/query/jobs');

        // check if POST or GET
        if ($this->getRequest()->isPost()) {
            if ($this->getParam('cancel')) {
                // user clicked cancel
                $this->getController()->redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->export($this->getRequest()->getPost());

                if ($response['status'] === 'ok') {
                    $filename = sprintf('jobs-%04d-%02d.csv', (int) $response['year'], (int) $response['month']);
                    $this->_helper->layout()->disableLayout();
                    $this->getResponse()->setHeader('Content-Type', 'text/csv');
                    $this->getResponse()->setHeader('Content-Disposition', "attachement; filename={$filename}");
                }
            }
        } else {
            // just display the form
            $response = $this->_model->export();
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->assign($response);
    }

}
