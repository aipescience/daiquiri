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

/**
 * @class   Daiquiri_Controller_AbstractREST AbstractREST.php
 * @brief   Abstract class for RESTfull daiquiri controllers
 * 
 * Abstract class for RESTfull daiquiri controllers providing commonly used methods. This
 * class extends the default Zend Controller.
 * 
 */
abstract class Daiquiri_Controller_AbstractREST extends Zend_Rest_Controller {

    //sets the error code of the response header and quits
    protected function _sendHttpError($errNum) {
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
        $this->getResponse()->setHttpResponseCode($errNum)->sendResponse();
        die(0);
    }

}
