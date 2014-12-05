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

class Meetings_ParticipantsController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Participants');
    }

    public function indexAction() {
        $slug = $this->_getParam('slug');
        $redirect = $this->_getParam('redirect','/meetings/meetings/');
        if ($slug !== null) {
            $model = Daiquiri_Proxy::factory('Meetings_Model_Meetings');
            $response = $model->show(array('slug' => $slug));
            $this->view->meeting = $response['row'];
        }

        $this->view->slug = $slug;
        $this->view->redirect = $redirect;
    }

    public function colsAction() {
        $this->getControllerHelper('pagination')->cols();
    }

    public function rowsAction() {
        $this->getControllerHelper('pagination')->rows();
    }

    public function exportAction() {
        $slug = $this->_getParam('slug');
        $status = $this->_getParam('status');
        $response = $this->_model->export($slug, $status);
        $this->view->mode = $this->_getParam('mode');
        $this->view->assign($response);

        // disable layout
        $this->_helper->layout->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }

    public function showAction() {
        $id = $this->getParam('id');
        $response = $this->_model->show($id);
        $this->view->redirect = $this->_getParam('redirect','/meetings/participants/');
        $this->view->assign($response);
        Zend_Debug::dump($response['row']['details']);
        Zend_Debug::dump($response['row']['contributions']); die(0);
    }

    public function createAction() {
        $slug = $this->_getParam('slug');
        $this->getControllerHelper('form',array(
            'redirect' => '/meetings/' . $slug . '/participants/'
        ))->create($slug);
    }

    public function updateAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->update($id);
    }

    public function deleteAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->delete($id);
    }

    public function acceptAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->accept($id);
    }

    public function rejectAction() {
        $id = $this->_getParam('id');
        $this->getControllerHelper('form')->reject($id);
    }
}
