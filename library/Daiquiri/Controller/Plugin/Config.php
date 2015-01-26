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

/**
 * @class   Daiquiri_Controller_Plugin_InitCheck InitCheck.php
 * @brief   Daiquiri InitCheck front controller plugin.
 * 
 * Class for the daiquiri front controller plugin handling errors.
 * 
 * Checks whether the Daiquiri configuration environment has been properly set.
 * 
 */
class Daiquiri_Controller_Plugin_Config extends Zend_Controller_Plugin_Abstract {

    /**
     * @brief   preDispatch method - called by Front Controller after dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * Checks whether the Daiquiri configuration environment has been properly set. If
     * not, raise error.
     * 
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        // set config and throw error if it fails
        if (Daiquiri_Config::getInstance()->setConfig() === false) {
            // throw error only if is not already thrown
            $this->getResponse()
                ->clearHeaders()
                ->setHttpResponseCode(503)
                ->setBody('<h1>The application is not correctly set up.</h1>')
                ->sendResponse();
            die(0);
        }
    }

}

