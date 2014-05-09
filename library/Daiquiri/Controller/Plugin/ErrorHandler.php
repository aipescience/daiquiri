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

class Daiquiri_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_ErrorHandler {

    /**
     * Module to use for errors.
     * @var string
     */
    protected $_errorModule = 'core';

    /**
     * Controller to use for errors.
     * @var string
     */
    protected $_errorController = 'error';

    /**
     * Action to use for errors.
     * @var string
     */
    protected $_errorAction = 'error';

    /**
     * Const - No controller exception; controller does not exist
     */
    const EXCEPTION_DAIQUIRI = 'EXCEPTION_DAIQUIRI';

    protected function _handleError(Zend_Controller_Request_Abstract $request) {

        $frontController = Zend_Controller_Front::getInstance();
        $response = $this->getResponse();

        if ($this->_isInsideErrorHandlerLoop) {
            $exceptions = $response->getException();
            if (count($exceptions) > $this->_exceptionCountAtFirstEncounter) {
                // Exception thrown by error handler; tell the front controller to throw it
                $frontController->throwExceptions(true);
                throw array_pop($exceptions);
            }
        }

        // check for an exception AND allow the error handler controller the option to forward
        if (($response->isException()) && (!$this->_isInsideErrorHandlerLoop)) {
            $this->_isInsideErrorHandlerLoop = true;

            // Get exception information
            $error            = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
            $exceptions       = $response->getException();
            $exception        = $exceptions[0];
            $exceptionType    = get_class($exception);
            $error->exception = $exception;

            switch ($exceptionType) {
                case 'Daiquiri_Exception_Unauthorized':
                    $this->setErrorHandlerModule("auth");
                    $this->setErrorHandlerController("error");
                    $this->setErrorHandlerAction("login");
                    $error->type = self::EXCEPTION_DAIQUIRI;
                    break;
                case 'Daiquiri_Exception_Forbidden':
                    $error->type = self::EXCEPTION_DAIQUIRI;
                    break;
                case 'Daiquiri_Exception_NotFound':
                    $error->type = self::EXCEPTION_DAIQUIRI;
                    break;
                case 'Daiquiri_Exception_BadRequest':
                    $error->type = self::EXCEPTION_DAIQUIRI;
                    break;
                case 'Zend_Controller_Router_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ROUTE;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                case 'Zend_Controller_Dispatcher_Exception':
                    $error->type = self::EXCEPTION_NO_CONTROLLER;
                    break;
                case 'Zend_Controller_Action_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ACTION;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                default:
                    $error->type = self::EXCEPTION_OTHER;
                    break;
            }

            // Keep a copy of the original request
            $error->request = clone $request;

            // get a count of the number of exceptions encountered
            $this->_exceptionCountAtFirstEncounter = count($exceptions);

            // Forward to the error handler
            $request->setParam('error_handler', $error)
                    ->setModuleName($this->getErrorHandlerModule())
                    ->setControllerName($this->getErrorHandlerController())
                    ->setActionName($this->getErrorHandlerAction())
                    ->setDispatched(false);
        }
    }

}
