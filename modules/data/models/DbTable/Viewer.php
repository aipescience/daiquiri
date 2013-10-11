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

class Data_Model_DbTable_Viewer extends Daiquiri_Model_DbTable_Abstract {
    
    /**
     * Constructs a Zend select object from a given array with sql options,
     * using the first (or a specified) database table.
     * @param Array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return Daiquiri_Model_DbTable_Select
     */
    public function getSelect($sqloptions = array()) {

        $select = new Daiquiri_Model_DbTable_Select($this);

        // set from
        $cols = $this->getCols($sqloptions);

        if (!empty($sqloptions['from'])) {
            $cols = array_intersect($sqloptions['from'], $cols);
        }
        // escape expressions with round brackets since Zend will not
        foreach($cols as &$currCol) {
            if (strpos($currCol, '(') !== false && strpos($currCol, ')') !== false) {
               $currCol = new Zend_Db_Expr($this->getAdapter()->quoteIdentifier($currCol));
            } else if (strpos($currCol, '.') !== false) {
               $quote = $this->getAdapter()->quoteIdentifier("");
               $currCol = new Zend_Db_Expr($quote[0] . $currCol . $quote[0]);
            }
        }
        $select->from($this, $cols);

        // set limit
        if (!empty($sqloptions['limit'])) {
            if (!empty($sqloptions['start'])) {
                $start = $sqloptions['start'];
            } else {
                $start = 0;
            }
            $select->limit($sqloptions['limit'], $start);
        }
        // set order
        if (!empty($sqloptions['order'])) {
            $select = $select->order($sqloptions['order']);
        }
        // set where statement
        if (!empty($sqloptions['where'])) {
            foreach ($sqloptions['where'] as $w) {
                $select = $select->where($w);
            }
        }
        
        // set where statement
        if (!empty($sqloptions['orWhere'])) {
            foreach ($sqloptions['orWhere'] as $w) {
                $select = $select->orWhere($w);
            }
        }

        // Zend_Debug::dump($select->__toString()); // die(0);
        
        // remove the join that is some times added by Zend, by getting rid of 
        // the FROM stuff, and adding it again...
        $select->reset( Zend_Db_Select::FROM );
        $select->from($this, $cols);

        return $select;
    }


}
