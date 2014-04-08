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

class Daiquiri_Model_Resource_Table extends Daiquiri_Model_Resource_Adapter {

    /**
     * Name of the database table used for the generic functions in this class. 
     * @var string
     */
    private $_tablename = null;

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
     * Fetches the columns of the previously set database table.
     * @return array $cols
     */
    public function fetchCols() {
        return array_keys($this->getAdapter()->describeTable($this->getTablename()));
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
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
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
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());

        // query database and return
        return $this->fetchAll($select);
    }

    /**
     * Fetches the primary key and one specified field from the previously set database table 
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
    public function insertRow(array $data = array()) {
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

}
