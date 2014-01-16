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

class Daiquiri_Model_Resource_Simple extends Daiquiri_Model_Resource_Abstract {

    /**
     * The name of the database table used for the generic functions in this class. 
     * @var string
     */
    protected $tablename = null;

    /**
     * Database adapter to be used with this resource. If null the default adapter will be used.
     * @var Zend_Db_Adapter
     */
    private $_adapter = null;

    /**
     * Sets the database adapter for this resource.
     * @param Zend_Db_Adapter database adapter
     */
    public function setAdapter($adapter) {
        $this->_adapter = $adapter;
    }

    /**
     * Returns the database adapter for this resource. Returns the default adapter if no adapter is set.
     * @return Zend_Db_Adapter
     */

    public function getAdapter() {
        if ($this->_adapter === null) {
            return Zend_Db_Table::getDefaultAdapter();
        } else {
            return $this->_adapter;
        }
    }

    /**
     * Sets a database table name to be used for the generic functions in this class.
     * @param string $tablename the name of the database table
     */
    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    /**
     * Returns the database table name to be used for the generic functions in this class.
     * @throws Exception
     * @return string
     */
    public function getTablename() {
        if (empty($this->_tablename)) {
            throw new Exception('No table was set.');
        } else {
            return $this->_tablename;
        }
    }

    /**
     * Constructs a Zend select object from a given array with sql options,
     * using the previously set database table.
     * @param Array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return Zend_Db_Select
     */
    public function getSelect($sqloptions = array()) {
        // get select object
        $select = $this->getAdapter()->select();

        // set from
        $cols = $this->fetchCols();
        if (!empty($sqloptions['from'])) {
            $cols = array_intersect($sqloptions['from'], $cols);
        }
        $select->from($this->getTablename(), $cols);

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
        
        // set or where statement
        if (!empty($sqloptions['orWhere'])) {
            foreach ($sqloptions['orWhere'] as $w) {
                $select = $select->orWhere($w);
            }
        }

        // Zend_Debug::dump($select->__toString()); // die(0);
        
        return $select;
    }

    /**
     * Fetches the columns of the previously set database table.
     * @param string $format format of the output (plain or db)
     * @return array
     */
    public function fetchCols($format = 'plain') {
        if (empty($tablename)){
            $tablename = $this->getTablename();
        }

        $desc = $this->getAdapter()->describeTable($this->getTablename());
        $cols = array_keys($desc);

        if ($format == 'db') {
            $dbCols = array();
            foreach ($cols as $col) {
                $dbCols[$col] = '`' . $this->getTablename() . '`.`' . $col . '`';
            }
            return $dbCols;
        } else {
            return $cols;
        }
    }

    /**
     * Fetches one row specified by its id from the previously set database table.
     * @param int $id
     * @throws Exception
     * @return Zend_Db_Table_Row_Abstract
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        // TODO get primary key

        $select = $this->getAdapter()->select();
        $select->from($this->getTablename());
        $select->where('id = ?', $id);

        // query database
        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception($id . ' not found in ' . get_class($this) . '::fetchRow()');
        }

        return $row;
    }

    /**
     * Fetches the id of one row specified by SQL keywords from the previously set database table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int
     */
    public function fetchId($sqloptions = array()) {
        // get select object
        $select = $this->getSelect($sqloptions);

        // query database
        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception('id not found in ' . get_class($this) . '::fetchId()');
        }

        return (int) $row['id'];
    }

    /**
     * Fetches a set of rows specified by SQL keywords from the previously set database table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return array
     */
    public function fetchRows($sqloptions = array()) {
        // get select object
        $select = $this->getSelect($sqloptions);

        // query database
        $rows = $this->getAdapter()->fetchAll($select);

        return $rows;
    }

    /**
     * Fetches one field and the id as a flat array from the previously set database table.
     * @param string $field name of the field
     * @return array
     */
    public function fetchValues($fieldname) {
        if (empty($fieldname)) {
            throw new Exception('$fieldname not provided in ' . get_class($this) . '::insertRow()');
        }

        // get select object
        $select = $this->getAdapter()->select();
        $select->from($this->getTablename(), array('id', $fieldname));

        // query database, construct array, and return
        $data = array();
        foreach($this->getAdapter()->fetchAll($select) as $row) {
            $data[$row['id']] = $row[$fieldname];
        }
        return $data;
    }

    /**
     * Counts the number of rows in the previously set database table.
     * @param @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int
     */
    public function countRows(array $sqloptions = null) {
        // get select object
        $select = $this->getAdapter()->select();
        $select->from($this->getTablename(), 'COUNT(*) as count');

        if ($sqloptions) {
            if (isset($sqloptions['where'])) {
                foreach ($sqloptions['where'] as $w) {
                    $select = $select->where($w);
                }
            }
            if (isset($sqloptions['orWhere'])) {
                foreach ($sqloptions['orWhere'] as $w) {
                    $select = $select->orWhere($w);
                }
            }
        }

        // query database
        $row = $this->getAdapter()->fetchRow($select);
        return (int) $row['count'];
    }

    /**
     * Inserts a row in the previously set database table according to the array $data.
     * @param array $data row data
     * @return int $id primary key (id) of the inserted row
     * @throws Exception
     */
    public function insertRow($data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::insertRow()');
        }

        $this->getAdapter()->insert($this->getTablename(), $data);
        return $this->getAdapter()->lastInsertId();
    }

    /**
     * Updates a row specified by its id in the previously set database table.
     * according to the array $data.
     * @param int $id id of the row
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, $data) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::insertRow()');
        }

        $this->getAdapter()->update($this->getTablename(), $data, array('id = ?' => $id));
    }

    /**
     * Deletes a row specified by its id from the previously set database table.
     * @param int $id id of the row
     * @param string $tablename the name of the database table
     * @throws Exception
     */
    public function deleteRow($id, $tablename = null) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::deleteRow()');
        }

        $this->getAdapter()->delete($this->getTablename(), array('id = ?' => $id));
    }

}
