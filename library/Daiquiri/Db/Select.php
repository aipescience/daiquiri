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

class Daiquiri_Db_Select extends Zend_Db_Select {

    public function __construct($adapter, $sqloptions) {
        parent::__construct($adapter);

        // set limit
        if (!empty($sqloptions['limit'])) {
            if (!empty($sqloptions['start'])) {
                $start = $sqloptions['start'];
            } else {
                $start = 0;
            }
            $this->limit($sqloptions['limit'], $start);
        }

        // set order
        if (!empty($sqloptions['order'])) {
            $this->order($sqloptions['order']);
        }

        // set where statement
        if (!empty($sqloptions['where'])) {
            foreach ($sqloptions['where'] as $key => $value) {
                if (is_string($key)) {
                    $where = $this->getAdapter()->quoteInto($key, $value);
                } else {
                    $where = $value;
                }
                $this->where($where);
            }
        }
        
        // set or where statement
        if (!empty($sqloptions['orWhere'])) {
            foreach ($sqloptions['orWhere'] as $key => $value) {
                if (is_string($key)) {
                    $where = $this->getAdapter()->quoteInto($key, $value);
                } else {
                    $where = $value;
                }
                $this->orWhere($where);
            }
        }
    }
}
