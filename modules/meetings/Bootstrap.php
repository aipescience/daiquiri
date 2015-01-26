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

class Meetings_Bootstrap extends Zend_Application_Module_Bootstrap {
    //setup rest for this module
    protected function _initRoute() {
        $this->bootstrap('frontController');
        $frontController = Zend_Controller_Front::getInstance();

        $router = new Zend_Controller_Router_Route(
            '/meetings/:slug/:controller/:action/*',
            // default values when the route matches
            array(
                'module' => 'meetings',
                'action' => 'index'
            ),
            // condition for the route
            array(
                'slug' => '^(?!(participants|contributions|registration)).*$',
                'controller' => '^(participants|contributions|registration|info).*$'
            )
        );

        $frontController->getRouter()->addRoute('meetings', $router);
    }
}
