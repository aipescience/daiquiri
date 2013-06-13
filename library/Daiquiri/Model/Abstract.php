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
 * @class   Daiquiri_Model_Abstract Abstract.php
 * @brief   Abstract base class for all models in the daiquiri framework.
 * 
 * Abstract base class for all models in the daiquiri framework. This provides
 * the following additional functionalities to each daiquiri model:
 *  - internalLink
 *  - getResource, setResource
 *  - createRandomString
 */
abstract class Daiquiri_Model_Abstract {

    /**
     * @var Daiquiri_Auth_Model_Resource_Abstract $_resource
     * Ressource object for the model
     */
    protected $_resource = null;

    /**
     * @brief   Empty default constructor.
     */
    public function __construct() {
        
    }

    /**
     * @brief   Proxy for the internal Link view helper
     * @param   array $options options of the link view helper
     * @return  string internal link
     * 
     * proxy function from Daiquiri_View_Helper_InternalLink
     */
    public function internalLink(array $options) {
        // get the view object
        $view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

        // return the link
        return $view->internalLink($options);
    }

    /**
     * @breif Returns the resource of the model.
     * @return type 
     */
    public function getResource() {
        return $this->_resource;
    }

    /**
     * @brief Sets the resource of the model.
     * @param string $resourcename
     */
    public function setResource($resourcename) {
        $this->_resource = new $resourcename();
    }

    /**
     * @brief Creates a random string of given length.
     * @param int $len
     * @return string 
     */
    public function createRandomString($len) {
        // produce random string
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $string = '';
        for ($i = 0; $i < $len; $i++) {
            $string .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $string;
    }

}
