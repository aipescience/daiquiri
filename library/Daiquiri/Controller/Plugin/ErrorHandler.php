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
 * @class   Daiquiri_Controller_Plugin_ErrorHandler ErrorHandler.php
 * @brief   Daiquiri ErrorHandler front controller plugin.
 * 
 * Class for the daiquiri front controller plugin handling errors.
 * 
 * If an error has occured that needs special treatement, add it here to the
 * postDispatch and handle it as you wish.
 * 
 */
class Daiquiri_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_Abstract {

    /**
     * @brief   postDispatch method - called by Front Controller after dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * Obtains the default Zend ErrorHandler and checks for certain errors that
     * need different handling.
     * 
     * The following exceptions are handled differently:
     *   - <b>Daiquiri_Exception_Forbidden</b>:  redirection to the login page
     * 
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        // get the front controller plugin and the zend error handler
        $front = Zend_Controller_Front::getInstance();
        $error = $front->getPlugin('Zend_Controller_Plugin_ErrorHandler');

        // redirect certain exeption to non-default error controllers
        if ($error->getResponse()->hasExceptionOfType('Daiquiri_Exception_Forbidden')) {
            $error->setErrorHandlerModule("auth");
            $error->setErrorHandlerController("error");
            $error->setErrorHandlerAction("login");
        }
    }

}
