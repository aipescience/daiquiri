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

class Meetings_ParticipantsController extends Daiquiri_Controller_CRUD {

    public function init() {
        parent::init();
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Participants');
    }

    public function createAction() {
        // get params
        $meetingId = $this->_getParam('meetingId'); 
        $redirect = $this->_getParam('redirect', $this->_options['index']['url']);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->create($meetingId, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->create($meetingId);
        }

        // set action for form
        if ($this->_options['create']['url'] !== null && array_key_exists('form', $response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . $this->_options['create']['url'] . '?meetingId=' . $meetingId;
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->title = $this->_options['create']['title'];
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }
}
