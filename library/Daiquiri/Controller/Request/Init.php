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
 * @class   Daiquiri_Controller_Request_Init Init.php
 * @brief   Daiquiri Command line "request" handler for init script usage.
 * 
 * Class for the daiquiri front controller plugin handling errors.
 * 
 * Request handler for the init.php setup script. Hanldes config key-value 
 * pairs to construct a proper Daiquiri request object for further usage.
 * 
 */
class Daiquiri_Controller_Request_Init extends Zend_Controller_Request_Simple {

    protected $_params = array();

    public function __construct() {
        parent::__construct('index', 'index');
    }

    /**
     * @brief   setParams method - sets the parameter array
     * @param   array $params: the parameter array with the configuration
     * 
     * Sets the parameter array.
     * 
     */
    public function setParams($params) {
        $this->_params = $params;
    }

    /**
     * @brief   getParams method - gets the parameter array
     * @return  the parameter array
     * 
     * Gets the parameter array.
     * 
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * @brief   getParam method - Get a specific parameter by key
     * @param   $key: the parameter key
     * @param   $default [null]: default value returned, when key does not exist
     * @return  the parameter value
     * 
     * Get a specific parameter by key.
     * 
     */
    public function getParam($key, $default = null) {
        if (array_key_exists($key, $this->_params)) {
            return $this->_params[$key];
        } else {
            return $default;
        }
    }

    /**
     * @brief   getServer method - returns the server name for given input
     * @param   $input: The type of server as specified in the HTML header 
     *                  (i.e. HTTP_USER_AGEN, REMOTE_ADDR)
     * @return string with the server ip address or name
     * 
     * Returns 'CLI' for $input = HTTP_USER_AGENT marking command line interface
     * mode. Returns '127.0.0.1' if $input = REMOTE_ADDR and NULL if unknown
     * 
     */
    public function getServer($input) {
        if ($input === 'HTTP_USER_AGENT') {
            return 'CLI';
        } elseif ($input === 'REMOTE_ADDR') {
            return '127.0.0.1';
        } else {
            return null;
        }
    }

}