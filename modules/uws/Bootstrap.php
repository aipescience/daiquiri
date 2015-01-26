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

class Uws_Bootstrap extends Zend_Application_Module_Bootstrap {

    protected function _initFrontControllerPlugins() {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Zend_Controller_Plugin_PutHandler());
    }

    //setup rest for this module
    protected function _initRestRoute() {
        $this->bootstrap('frontController');
        $frontController = Zend_Controller_Front::getInstance();
        $restRouteUL = new Daiquiri_Controller_Router_Rest($frontController,
                        "/uws/:moduleName/*",
                        array('module' => 'uws', 'controller' => 'index'));
        $frontController->getRouter()->addRoute('rest', $restRouteUL);
    }

}
