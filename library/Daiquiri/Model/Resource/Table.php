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
        $cols = array();
        foreach($this->getAdapter()->describeTable($this->getTablename()) as $col) {
            $cols[$col['COLUMN_NAME']] = $this->quoteIdentifier($col['TABLE_NAME'], $col['COLUMN_NAME']);
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
        return false;
    }

    /**
     * Fetches one row specified by its primary key or an array of sqloptions
     * from the previously set database table.
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
            $select->setWhere($sqloptions);
            $select->setOrWhere($sqloptions);
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
