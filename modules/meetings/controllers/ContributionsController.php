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

class Meetings_ContributionsController extends Daiquiri_Controller_AbstractCRUD {

    public function init() {
        parent::init();
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Contributions');
    }

    public function indexAction() {
        // get params
        $meetingId = $this->_getParam('meetingId'); 

        // get the data
        $response = $this->_model->index($meetingId);

        // assign to view
        $this->view->meetingId = $meetingId;
        $this->view->options = $this->_options;
        $this->view->model = $this->_model->getClass();
        $this->setViewElements($response);
    }

    public function createAction() {
        // get params
        $meetingId = $this->_getParam('meetingId'); 
        $redirect = $this->_getParam('redirect','/meetings/contributions/');

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
        $this->view->title = $this->_options['create']['title'];
        $this->setViewElements($response, $redirect);
    }

    public function acceptAction() {
        // get params from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect','/meetings/contributions/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->accept($id, $this->_request->getPost());
            }
        }  else {
            // just display the form
            $response = $this->_model->accept($id);
        }

        // set action for form
        $this->setFormAction($response, '/meetings/contributions/accept?id=' . $id);

        // assign to view
        $this->setViewElements($response, $redirect);
    }

    public function rejectAction() {
        // get params from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect','/meetings/contributions/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->reject($id, $this->_request->getPost());
            }
        }  else {
            // just display the form
            $response = $this->_model->reject($id);
        }

        // set action for form
        $this->setFormAction($response, '/meetings/contributions/reject?id=' . $id);
        
        // assign to view
        $this->setViewElements($response, $redirect);
    }
}
