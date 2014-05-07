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

class Meetings_ContributionsController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Contributions');
    }

    public function indexAction() {
        $meetingId = $this->_getParam('meetingId');
        if ($meetingId !== null) {
            $model = Daiquiri_Proxy::factory('Meetings_Model_Meetings');
            $response = $model->show($meetingId);
            $this->view->meetingTitle = $response['row']['title'];
        }
        $this->view->meetingId = $meetingId;
    }

    public function colsAction() {
        $this->getControllerHelper('pagination')->cols();
    }

    public function rowsAction() {
        $this->getControllerHelper('pagination')->rows();
    }

    public function showAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('table')->show($id);
    }

    public function createAction() {
        $meetingId = $this->_getParam('meetingId');
        $this->getControllerHelper('form',array('redirect' => '/meetings/contributions/?meetingId=' . $meetingId))->create($meetingId);
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
