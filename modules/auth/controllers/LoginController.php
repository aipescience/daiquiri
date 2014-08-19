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
            if (strpos($cookie, 'wordpress_') === 0 || strpos($cookie, 'wp-settings') === 0) {
                $cookiePath = Daiquiri_Config::getInstance()->getBaseUrl();
                if (empty($cookiePath)) {
                    $cookiePath = '/';
                }
                setcookie($cookie, ' ', time() - 31536000, $cookiePath);
            }
        }

        $this->redirect($redirect);
    }

}
