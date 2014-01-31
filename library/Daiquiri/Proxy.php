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
     * @throws Daiquiri_Exception_AuthError
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
            throw new Daiquiri_Exception_AuthError('Not Authorised in ' . get_class($this->_model) . '::' . $methodname);
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