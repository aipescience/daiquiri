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

class Auth_LoginController extends Daiquiri_Controller_Abstract {

    public function indexAction() {
        // get redirect url
        $redirect = $this->_getParam('redirect', '/');

        // get model NOT poxied since acl are not necessarily there
        $model = new Auth_Model_Login();

        // check if already logged in
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // redirect to index
            $this->_helper->redirector('index', 'index', 'default');
        } else {
            // check if POST or GET
            if ($this->_request->isPost()) {
                if ($this->_getParam('cancel')) {
                    // user clicked cancel
                    $this->_redirect($redirect);
                } else {
                    // validate form and login
                    $response = $model->login($this->_request->getPost());
                }
            } else {
                // just display the form
                $response = $model->login();
            }

            if ($response['status'] === 'redirect') {
                // set cookies
                if (!empty($response['cookies'])) {
                    foreach ($response['cookies'] as $cookie) {
                        $this->getResponse()->setHeader('Set-cookie', $cookie);
                    }
                }
                $this->_redirect($redirect);
            } else {
                // assign to view        
                foreach ($response as $key => $value) {
                    $this->view->$key = $value;
                }
            }
        }
    }

    public function logoutAction() {
        // get redirect url
        $redirect = $this->_getParam('redirect', '/');

        // get model NOT poxied since acl are not necessarily there
        $model = new Auth_Model_Login();
        $response = $model->logout();

        // set cookies
        foreach ($this->_request->getCookie() as $cookie => $value) {
            if (strpos($cookie, 'wordpress_') === 0) {
                setcookie($cookie, '', 0, '/');
            }
        }

        $this->redirect($redirect);
    }

}
