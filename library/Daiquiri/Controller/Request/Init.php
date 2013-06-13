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