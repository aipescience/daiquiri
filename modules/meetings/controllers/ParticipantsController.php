<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class Meetings_ParticipantsController extends Daiquiri_Controller_Abstract {

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Participants');
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
        $this->getControllerHelper('form',array('redirect' => '/meetings/participants/?meetingId=' . $meetingId))->create($meetingId);
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
