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

/**
 *  Controller for the submission a message via the contact form.
 */
class Contact_IndexController extends Daiquiri_Controller_Abstract {

    public function init() {
        
    }

    public function indexAction() {

        $model = Daiquiri_Proxy::factory('Contact_Model_Submit');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_helper->redirector('index', 'index', 'default');
            } else {
                // validate form
                $response = $model->contact($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->contact();
        }

        // assign to view
        $this->view->status = $response['status'];
        $this->view->form = $response['form'];
    }

}
