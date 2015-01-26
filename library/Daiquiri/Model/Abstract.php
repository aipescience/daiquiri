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
    private $_resource = null;

    private $_model_helper = array();


    /**
     * @brief   Empty default constructor.
     */
    public function __construct() {
        
    }

    public function getModelHelper($helper, array $options = array()) {
        if (empty($this->_model_helper[$helper])) {
            $helperclass = 'Daiquiri_Model_Helper_' . ucfirst($helper);
            $this->_model_helper[$helper] = new $helperclass($this, $options);
        }

        return $this->_model_helper[$helper];
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
