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
 * @class   Daiquiri_Model_SimpleTable SimpleTable.php
 * @brief   Abstract base class for all Table models with 
 *          one of the columns holding privileged values.
 * 
 * Abstract base class for all Table models, which abstract a simple table with 
 * one of the columns holding privileged values. This is the case for, e.g. simple 
 * list with just an id and a value.
 * 
 * This class is used, when working on a specific column. It sort of resembles a
 * basic value store. Data of the given column is then accessed through 
 * an 'id' (colname 'id').
 * 
 * An active column needs to be set through setValueField.
 */
abstract class Daiquiri_Model_SimpleTable extends Daiquiri_Model_Abstract {

    /**
     * @var string $_valueField
     * Name of the colum holding the primary value of the table;
     */
    protected $_valueField = null;

    /**
     * @brief   Sets the value field (column).
     * @param   string $field column name in database table
     * 
     * This function sets the table column that is linked to the SimpleTable class.
     * Needs to be a column name in a database table.
     */
    public function setValueField($field) {
        $this->_valueField = $field;
    }

    /**
     * @brief   Return the value Field.
     * @return  string name of currently active column
     */
    public function getValueField() {
        return $this->_valueField;
    }

    /**
     * @brief   Returns the whole table as an array.
     * @return  array
     * 
     * Returns the complete table ordered by 'id' in ascending order.
     */
    public function getTable() {
        // get rows from databse
        return $this->getResource()->fetchRows(array('order' => 'id asc'));
    }

    /**
     * @brief   Returns only the value column as an array.
     * @throws  Exception
     * @return  array
     * 
     * Returns all the values in the column specified through setValueField 
     * sorted by 'id' in ascending order.
     * 
     * The resulting array will 'id' as key and column row value as value.
     */
    public function getValues() {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // get rows from databse
        $rows = $this->getResource()->fetchRows(array('order' => 'id asc',
            'from' => array('id', $this->_valueField)));

        // convert to flat array and return
        $values = array();
        foreach ($rows as $row) {
            $values[$row['id']] = $row[$this->_valueField];
        }
        return $values;
    }

    /**
     * @brief   Returns a specific value given by $id.
     * @param   int $id
     * @throws  Exception
     * @throws  Daiquiri_Exception_RuntimeError
     * @return  string
     * 
     * Returns a specific value from the value column given by $id. Will throw
     * Exception if value field is not set. Throws Daiquiri_Exception_RuntimeError
     * when $id is not found in database.
     */
    public function getValue($id) {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // get row from the database
        $row = $this->getResource()->fetchRow($id);

        // get the role and return
        if ($row) {
            return $row[$this->_valueField];
        } else {
            throw new Daiquiri_Exception_RuntimeError('id not found in database in ' . __METHOD__);
        }
    }

    /**
     * @brief   Returns the id of a specific value.
     * @param   string $value
     * @throws  Exception
     * @return  int
     * 
     * Given the value, will return the corresponding id. If multiple ids map to
     * the given value, the first returned id will be returned. Will return null
     * if nothing is found.
     * 
     * Will throw Exception, if value field is not set.
     */
    public function getId($value) {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // escape where statment
        $adapter = $this->getResource()->getTable()->getAdapter();
        $field = $adapter->quoteIdentifier($this->_valueField);
        $where = $adapter->quoteInto($field . ' = ?', $value);

        // get rows from databse
        $rows = $this->getResource()->fetchRows(array('where' => array($where)));

        // get the role id and return
        if (!empty($rows)) {
            return $rows[0]['id'];
        } else {
            return null;
        }
    }

    /**
     * @brief   Add a value to the table in the database.
     * @param   string $value
     * @throws  Exception
     * 
     * Adds a value to the column. Basically this results in a new row being
     * added to the table.
     * 
     * Throws Exception if value field is not set.
     */
    public function addValue($value) {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // insert into database
        $this->getResource()->insertRow(array($this->_valueField => $value));
    }

}
