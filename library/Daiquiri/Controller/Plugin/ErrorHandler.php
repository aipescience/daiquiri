<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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
