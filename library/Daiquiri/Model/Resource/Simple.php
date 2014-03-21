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
     * Name of the database table used for the generic functions in this class. 
     * @var string
     */
    private $_tablename = null;

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
            throw new Exception('No tablename was set.');
        } else {
            return $this->_tablename;
        }
    }

    /**
     * Returns a Daiquiri select object from a given array with sql options.
     * @param Array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return Daiquiri_Db_Select
     */
    public function select($sqloptions = array()) {
        return new Daiquiri_Db_Select($this->getAdapter(), $sqloptions);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchAll(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object as associative array.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchAssoc(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchAssoc($select);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object as key value pairs.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchPairs(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Fetches one (and only one) row from the database specfied by the select object. 
     * Raises an Exception when more than one row is found. Returns an empty arrau when 
     * no rows are found
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @throws Exception
     * @return array $row
     */
    public function fetchOne(Daiquiri_Db_Select $select = null) {
        $rows = $this->getAdapter()->fetchAll($select);
        if (empty($rows)) {
            return array();
        } else if (count($rows) > 1) {
            throw new Exception('More than one row returned in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        } else {
            return $rows[0];
        }
    }

    /**
     * Fetches the columns of the previously set database table.
     * @return array $cols
     */
    public function fetchCols() {
        $desc = $this->getAdapter()->describeTable($this->getTablename());

        $cols = array();
        foreach(array_keys($desc) as $col) {
            $cols[$col] = $this->quoteIdentifier($this->getTablename(),$col);
        }
        return $cols;
    }

    /**
     * Fetches the primary key of the previously set database table.
     * @return string $colname
     */
    public function fetchPrimary() {
        $desc = $this->getAdapter()->describeTable($this->getTablename());
        foreach ($desc as $colname => $col) {
            if ($col['PRIMARY'] === true) {
                return $colname;
            }
        }
    }

    /**
     * Fetches one row specified by its primary key from the previously set database table.
     * @param mixed $input primary key of the row OR array of sqloptions
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($input) {
        if (empty($input)) {
            throw new Exception('$id or $sqloptions not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if (is_array($input)) {
            $select = $this->select($input);
            $select->from($this->getTablename());
        } else {
            $select = $this->select();
            $select->from($this->getTablename());
            $identifier = $this->quoteIdentifier($this->fetchPrimary());
            $select->where($identifier . '= ?', $input);
        }

        // get the rows an chach that its one and only one
        return $this->fetchOne($select);
    }

    /**
     * Fetches the id of one row specified by SQL keywords from the previously set database table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $rowId
     */
    public function fetchId($sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());
        
        // query database
        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return (int) $row[$this->fetchPrimary()];
        }
    }

    /**
     * Fetches a set of rows specified by SQL keywords from the previously set database table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return array $rows
     */
    public function fetchRows($sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());

        // query database and return
        return $this->fetchAll($select);
    }

    /**
     * Fetches primary key and one specified field from the previously set database table 
     * as a flat array.
     * @param string $field name of the field
     * @return array $rows
     */
    public function fetchValues($fieldname) {
        if (empty($fieldname)) {
            throw new Exception('$fieldname not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // get the name of the primary key
        $primary = $this->fetchPrimary();

        // get select object
        $select = $this->select();
        $select->from($this->getTablename(), array($primary, $fieldname));

        // query database, construct array, and return
        $rows = array();
        foreach($this->fetchAll($select) as $row) {
            $rows[$row[$primary]] = $row[$fieldname];
        }
        return $rows;
    }

    /**
     * Counts the number of rows in the previously set database table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null) {
        $select = $this->select();
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

        // query database and return
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }

    /**
     * Inserts a row in the previously set database table according to the array $data
     * and returns the primary key of the new row.
     * @param array $data row data
     * @return int
     * @throws Exception
     */
    public function insertRow($data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $this->getAdapter()->insert($this->getTablename(), $data);
        return $this->getAdapter()->lastInsertId();
    }

    /**
     * Updates a row specified by its primary key in the previously set database table.
     * according to the array $data.
     * @param int $id primary key of the row
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, $data) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }
        $identifier = $this->getAdapter()->quoteIdentifier($this->fetchPrimary());
        $this->getAdapter()->update($this->getTablename(), $data, array($identifier . '= ?' => $id));
    }

    /**
     * Deletes a row specified by its primary key from the previously set database table.
     * @param int $id primary key of the row
     * @throws Exception
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }
        $identifier = $this->getAdapter()->quoteIdentifier($this->fetchPrimary());
        $this->getAdapter()->delete($this->getTablename(), array($identifier . '= ?' => $id));
    }

    /**
     * Quotes a variable number of strings as databases idetifiers.
     * @param string $argument string to be quoted
     * @param string $argument string to be quoted
     * @param string $argument string to be quoted
     * @return string $identifier
     */

    public function quoteIdentifier() {
        // get the arguments
        $arguments = func_get_args();

        $identifier = array();
        foreach($arguments as $argument) {
            $identifier[] = $this->getAdapter()->quoteIdentifier($argument);
        }

        return implode('.', $identifier);
    }

}
