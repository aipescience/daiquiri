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
 * @class   Daiquiri_Controller_Plugin_Authorization Authorization.php
 * @brief   Daiquiri API front controller plugin.
 * 
 * Class for the daiquiri front controller plugin handling the treatment of
 * the API functionality.
 * 
 */
class Daiquiri_Controller_Plugin_Authorization extends Zend_Controller_Plugin_Abstract {

    private $_active; //!< bool encoding if API key has been set or not

    /**
     * @brief   preDispatch method - called by Front Controller before dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * preDispatch plugin checking for the basic authorization in the http header 
     * of the request. If the basic authorization header is given the user is 
     * authenticated for this request only. If the authetication is not successful
     * a HTML 401 is returned.
     * 
     * <b>Side effects:</b> Throws HTML 401 if API key does not match defined
     *                      one.
     * 
     */

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        parent::preDispatch($request);

        $username = null;
        $password = null;

        if(isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
        }

        if(isset($_SERVER['PHP_AUTH_PW'])) {
            $password = $_SERVER['PHP_AUTH_PW'];
        }

        // get the authorisation headers
        if (!empty($username) && !empty($password)) {
            // try to authenticate as user
            $result = Daiquiri_Auth::getInstance()->authenticateUser($username, $password);

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

            $this->_active = true;
        }
    }

    /**
     * @brief   postDispatch method - called by Front Controller after dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * postDispatch plugin for destroying the session if a user was authenticated
     * by the http authorization header.
     * 
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        parent::postDispatch($request);

        if ($this->_active) {
            // get the front controller plugin and the zend error handler
            $front = Zend_Controller_Front::getInstance();
            $error = $front->getPlugin('Zend_Controller_Plugin_ErrorHandler');

            // check if an exception is present
            if (!$error->getResponse()->isException()) {
                // destroy the session
                Zend_Session::destroy();
            }
        }
    }

}
