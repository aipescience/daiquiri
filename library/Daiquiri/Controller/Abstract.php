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
 * @class   Daiquiri_Controller_Abstract Abstract.php
 * @brief   Abstract class for daiquiri controllers
 * 
 * Abstract class for daiquiri controllers providing commonly used methods. This
 * class extends the default Zend Controller.
 * 
 */
abstract class Daiquiri_Controller_Abstract extends Zend_Controller_Action {

    private $_controller_helper = array();

    public function getModel() {
        return $this->_model;
    }

    public function getControllerHelper($helper, array $options = array()) {
        if (empty($this->_controller_helper[$helper])) {
            $helperclass = 'Daiquiri_Controller_Helper_' . ucfirst($helper);
            $this->_controller_helper[$helper] = new $helperclass($this, $options);
        }

        return $this->_controller_helper[$helper];
    }

    public function setFormAction($response, $url = null) {
        if (array_key_exists('form', $response)) {
            if ($url === null) {
                $action = $this->getRequest()->getRequestUri();
            } else {
                $action = Daiquiri_Config::getInstance()->getBaseUrl() . $url;
            }

            $form = $response['form'];
            $form->setAction($action);
        }
    }

    public function internalLink(array $options) {
        // get the view object
        $view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

        // return the link
        return $view->internalLink($options);
    }
}
