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
 * Base class for simple DbTable objects.
 */
class Daiquiri_Model_DbTable_Simple extends Daiquiri_Model_DbTable_Abstract {
    
    public function __construct($tablename = null, $dbname = null) {
        parent::__construct();
        
        if (empty($tablename)) {
            // throw new Exception('$tablename not provided in ' . get_class($this) . '::__construct()');
        } else {
            $this->setName($tablename);
        }
        if (!empty($dbname)) {
            $this->setDb($dbname);
        }
    }

}
