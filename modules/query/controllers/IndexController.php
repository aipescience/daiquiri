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

class Query_IndexController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        // check acl
        if (Daiquiri_Auth::getInstance()->checkAcl('Query_Model_Form', 'submit')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_AuthError();
        }
    }

    public function indexAction() {
        $this->view->status = 'ok';

        // get the query message
        $messagesModel = new Config_Model_Messages();
        $response = $messagesModel->show('query');
        $this->view->message = $response['row']['value'];

        // get the forms to display
        if (Daiquiri_Config::getInstance()->query->forms) {
            $this->view->forms = Daiquiri_Config::getInstance()->query->forms->toArray();
        } else {
            $this->view->forms = array();
        }
    }
}
