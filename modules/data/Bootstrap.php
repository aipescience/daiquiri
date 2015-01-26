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

class Data_Bootstrap extends Zend_Application_Module_Bootstrap {

    protected function _initRoute() {
        $this->bootstrap('frontController');
        $front = Zend_Controller_Front::getInstance();

        $router = new Zend_Controller_Router_Route_Regex(
            // regexp for the route, matches to (excluding the zend controllers we still need)
            // data/static/:alias/:path
            'data/((?!columns|databases|files|functions|index|static|tables|viewer)[a-z0-9A-Z\_\-]+)([a-z0-9A-Z\/\.\_\-]*)',
            // default values when the route matches
            array(
                'module' => 'data',
                'controller' => 'static',
                'action' => 'serve'
            ),
            // the third argument maps regexp matches to request params
            array(
                1 => 'alias',
                2 => 'path'
            ),
            // this is the reversed route, we need it so the error handling works
            'data/%s/%s'
        );

        $front->getRouter()->addRoute('static', $router);
    }

}
