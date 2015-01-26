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

class Daiquiri_Controller_Helper_Abstract {

    protected $_controller;
    protected $_model;

    public function __construct($controller) {
        $this->_controller = $controller;
        $this->_model = $controller->getModel();

        $this->init();
    }

    public function init() {
        // does nothing so far
    }

    public function getController() {
        return $this->_controller;
    }

    public function getParam($paramName, $default = null) {
        return $this->_controller->getParam($paramName, $default);
    }

    public function getRequest() {
        return $this->_controller->getRequest();
    }

    public function getView() {
        return $this->_controller->view;
    }

    public function getModel() {
        return $this->_model;
    }
}