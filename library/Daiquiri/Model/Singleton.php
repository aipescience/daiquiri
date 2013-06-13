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
 * @class   Daiquiri_Model_Singleton Singleton.php
 * @brief   Abstract base class for singleton objects in Daiquiri.
 * 
 * Abstract base class for singleton objects in Daiquiri.
 * 
 * for more information see:
 * http://danbettles.blogspot.de/2008/10/implementing-singleton-base-class-in.html
 */
abstract class Daiquiri_Model_Singleton {

    /**
     * @brief Constructor
     * 
     * Is declared here to make sure that it does not take any arguments
     */
    abstract protected function __construct();

    /**
     * @brief       Returns the instance of the singleton object
     * @staticvar   array $instances
     * @return      Daiquiri_Model_Singleton 
     */
    final public static function getInstance() {
        static $instances = array();

        $className = get_called_class();

        if (!isset($instances[$className])) {
            $instances[$className] = new $className();
        }

        return $instances[$className];
    }

    /**
     * @brief       Clone functionality is disabled
     */
    final private function __clone() {
        
    }

}
