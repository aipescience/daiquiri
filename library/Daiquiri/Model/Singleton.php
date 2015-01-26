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
