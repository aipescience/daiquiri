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

/**
 * Proxy in which all models should be embedded in.
 */
class Daiquiri_Proxy {

    /**
     * Model this class acts as proxy for.
     * @var Daiquiri__Model_Abstract
     */
    protected $_model;

    /**
     * Constructor. Asigns model class to proxy.
     * @param Daiquiri_Model_Abstract $object 
     */
    public function __construct(Daiquiri_Model_Abstract $object) {
        $this->_model = $object;
    }

    /**
     * Proxies calls to member function to the model. Implements security layer.
     * @param string $methodname
     * @param array $arguments
     * @throws Daiquiri_Exception_Forbidden
     * @return type (return value of model function)
     */
    public function __call($methodname, array $arguments) {
        // check if method exists
        if (!method_exists($this->_model, $methodname)) {
            throw new Exception('Method ' . $methodname . ' not found in ' . get_class($this->_model));
        }

        // check the acl
        $result = Daiquiri_Auth::getInstance()->checkMethod(get_class($this->_model), $methodname);

        // call function or throw exception if not allowed
        if ($result === true) {
            return call_user_func_array(array($this->_model, $methodname), $arguments);
        } else {
            throw new Daiquiri_Exception_Forbidden('Not Authorised in ' . get_class($this->_model) . '::' . $methodname);
        }
    }

    public function getClass() {
        return get_class($this->_model);
    }

    /**
     * Constructs the proxy and the model.
     * @return Daiquiri_Proxy
     * @throws Exception
     */
    static function factory() {
        // get the arguments
        $arguments = func_get_args();

        if (is_string($arguments[0])) {

            // get name of the class from the first entry in arguments
            $className = array_shift($arguments);

            // create model object using reflection
            $reflector = new ReflectionClass($className);
            $object = $reflector->newInstanceArgs($arguments);

            // create proxy for object
            $proxy = new Daiquiri_Proxy($object);

            // return the newly fabricated proxy
            return $proxy;
        } else {
            throw new Exception('Invalid classname provides in ' . __METHOD__);
        }
    }

}