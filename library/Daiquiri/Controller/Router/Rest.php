<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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

//this is code taken from:
//https://github.com/aporat/Application_Rest_Controller_Route/blob/master/library/Application/Rest/Controller/Route.php
//
//Nice REST routing from: https://github.com/aporat/Application_Rest_Controller_Route
class Daiquiri_Controller_Router_Rest extends Zend_Controller_Router_Route {

    /**
     * @var Zend_Controller_Front
     */
    protected $_front;
    protected $_actionKey = 'action';

    /**
     * Prepares the route for mapping by splitting (exploding) it
     * to a corresponding atomic parts. These parts are assigned
     * a position which is later used for matching and preparing values.
     *
     * @param Zend_Controller_Front $front Front Controller object
     * @param string $route Map used to match with later submitted URL path
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param array $reqs Regular expression requirements for variables (keys as variable names)
     * @param Zend_Translate $translator Translator to use for this instance
     */
    public function __construct(Zend_Controller_Front $front, $route, $defaults = array(), $reqs = array(), Zend_Translate $translator = null, $locale = null) {
        $this->_front = $front;
        $this->_dispatcher = $front->getDispatcher();

        $this->_route = $route;

        parent::__construct($route, $defaults, $reqs, $translator, $locale);
    }

    /**
     * Matches a user submitted path with parts defined by a map. Assigns and
     * returns an array of variables on a successful match.
     *
     * @param string $path Path used to match against this routing map
     * @return array|false An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false) {

        $return = parent::match($path, $partial);

        // add the RESTful action mapping
        if ($return) {
            //add the data that is a wildcard to the end of the return array
            $path = trim($path, self::URI_DELIMITER);
            $this->_route = trim($this->_route, self::URI_DELIMITER);

            if ($path != '') {
                $elementsPath = explode(self::URI_DELIMITER, $path);
            }

            if ($this->_route != '') {
                $elementsRoute = explode(self::URI_DELIMITER, $this->_route);
            }

            //and now get rid of all entries that are not a wildcard. when we have done that, 
            //we have what we wanted...
            foreach ($elementsRoute as $key => $value) {
                if ($value !== "*") {
                    array_shift($elementsPath);
                } else {
                    break;
                }
            }

            $i = 0;
            foreach ($elementsPath as $value) {
                $return['wild' . $i] = $value;
                $i++;
            }

            //reset things
            $request = $this->_front->getRequest();
            $params = $request->getParams();

            $path = $elementsPath;

            //Store path count for method mapping
            $pathElementCount = count($path);

            // Determine Action
            $requestMethod = strtolower($request->getMethod());
            if ($requestMethod != 'get') {
                if ($request->getParam('_method')) {
                    $return[$this->_actionKey] = strtolower($request->getParam('_method'));
                } elseif ($request->getHeader('X-HTTP-Method-Override')) {
                    $return[$this->_actionKey] = strtolower($request->getHeader('X-HTTP-Method-Override'));
                } else {
                    $return[$this->_actionKey] = $requestMethod;
                }
            } else {
                // this is only an index call, if no options are acutally provided
                if ($pathElementCount > 0) {
                    $return[$this->_actionKey] = 'get';
                } else {
                    $return[$this->_actionKey] = 'index';
                }
            }
        }

        return $return;
    }

}
