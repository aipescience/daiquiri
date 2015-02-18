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

class Daiquiri_Controller_Plugin_Authorization extends Zend_Controller_Plugin_Abstract {

    /**
     * Boolian if username and password have been set for this request or not
     * @var bool
     */
    private $_active;

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        parent::preDispatch($request);

        $username = null;
        $password = null;

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
        } else if ($request->getParam('username') !== null) {
            $username = $request->getParam('username');
            $request->getParam('username',null);
        }

        if (isset($_SERVER['PHP_AUTH_PW'])) {
            $password = $_SERVER['PHP_AUTH_PW'];
        } else if ($request->getParam('password')) {
            $password = $request->getParam('password');
            $request->getParam('password',null);
        }

        // get the authorisation headers
        if (!empty($username) && !empty($password)) {
            // try to authenticate as user
            $result = Daiquiri_Auth::getInstance()->authenticateUser($username, $password);

            if (!$result) {
                // try to authenticate with the samp token
                $result = Daiquiri_Auth::getInstance()->authenticateToken($username, $password, $request->getPathInfo());

                if (!$result) {
                    // try to authenticate as app
                    $result = Daiquiri_Auth::getInstance()->authenticateApp($username, $password);

                    if (!$result) {
                        $this->getResponse()
                                ->clearHeaders()
                                ->setHttpResponseCode(401)
                                ->sendResponse();
                        die(0);
                    }
                }

            }

            Daiquiri_Auth::getInstance()->unsetCsrf();
            $this->_active = true;
        }
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        parent::postDispatch($request);

        if ($this->_active) {
            // get the front controller plugin and the zend error handler
            $front = Zend_Controller_Front::getInstance();
            $error = $front->getPlugin('Daiquiri_Controller_Plugin_ErrorHandler');

            // check if an exception is present
            if (!$error->getResponse()->isException()) {
                // destroy the session
                Zend_Session::destroy();
            }
        }
    }

}
