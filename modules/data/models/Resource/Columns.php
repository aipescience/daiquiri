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

class Data_Model_Resource_Columns extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Columns');
    }

    /**
     * Fetches all column entries.
     * @return array $rows
     */
    public function fetchRows() {
        $select = $this->select();
        $select->from('Data_Columns');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches the id of one table entry specified database, the table and the column name.
     * @param string $db name of the database
     * @param string $table name of the table
     * @param string $table name of the column
     * @return int $id
     */
    public function fetchId($db, $table, $column) {
        $select = $this->select();
        $select->from('Data_Columns');
        $select->join('Data_Tables','`Data_Tables`.`id` = `Data_Columns`.`table_id`');
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`');
        $select->where("`Data_Databases`.`name` = ?", trim($db));
        $select->where("`Data_Tables`.`name` = ?", trim($table));
        $select->where("`Data_Columns`.`name` = ?", trim($column));

        // query database
        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception('id not found in ' . get_class($this) . '::fetchId()');
        }

        return (int) $row['id'];
    }

    /**
     * Fetches one column entry specified by its id.
     * @param int $id id of the row
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $columns = false) {
        $select = $this->select();
        $select->from('Data_Columns');
        $select->where("`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        // get the rowset and check if its one and only one
        $rows = $this->getAdapter()->fetchAll($select);
        if (empty($rows)) {
            throw new Exception('Row not found in ' . get_class($this) . '::fetchRow()');
        } else if (count($rows) > 1) {
            throw new Exception('More than one row returned in ' . get_class($this) . '::fetchRow()');
        } else {
            return $rows[0];
        }
    }
}
