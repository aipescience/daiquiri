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

class Data_Model_Resource_Tables extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Tables');
    }

    /**
     * Fetches all table entries.
     * @return array $rows
     */
    public function fetchRows() {
        $select = $this->select();
        $select->from('Data_Tables');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchAll($select);
    }

    /**
     * Fetches the id of one table entry specified database and the table name.
     * @param string $db name of the database
     * @param string $table name of the table
     * @return int $id
     */
    public function fetchId($db, $table) {
        if (empty($db) || empty($table)) {
            throw new Exception('$db or $table not provided in ' . get_class($this) . '::fetchId()');
        }

        $select = $this->select();
        $select->from('Data_Tables');
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array());
        $select->where("`Data_Databases`.`name` = ?", trim($db));
        $select->where("`Data_Tables`.`name` = ?", trim($table));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return (int) $row['id'];
        }
    }

    /**
     * Fetches one table entry specified by its id.
     * @param int $id id of the row
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $columns = false) {
        if (empty($id)) {
            throw new Exception('$id or $table not provided in ' . get_class($this) . '::fetchRow()');
        }

        $select = $this->select();
        $select->from('Data_Tables');
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array(
            'database' => 'name', 'database_id' => 'id'
        ));
        $select->where("`Data_Tables`.`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        $row = $this->fetchOne($select);

        if ($columns === true) {
            $select = $this->select();
            $select->from('Data_Columns');
            $select->where('table_id = ?', $row['id']);
            $select->order('order ASC');
            $select->order('name ASC');

            $row['columns'] = $this->getAdapter()->fetchAll($select);
        }

        return $row;
    }

    /**
     * Fetches primary key and table entry (including database)
     * as a flat array.
     * @return array $rows
     */
    public function fetchValues() {
        // get the name of the primary key
        $primary = $this->fetchPrimary();

        // get select object
        $select = $this->select();
        $select->from('Data_Tables', array('id', 'name'));
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array(
            'database' => 'name'
        ));

        // query database, construct array, and return
        $rows = array();
        foreach($this->fetchAll($select) as $row) {
            $rows[$row['id']] = $row['database'] . '.' . $row['name'];
        }
        return $rows;
    }

    /**
     * Inserts one table entry and, optionally, fills the columns with information from 
     * the database or a provided array.
     * Returns the primary key of the new row.
     * @param array $data row data
     * @param bool $autofill automatically fill the columns
     * @param array $tableDescription information for the table
     * @throws Exception
     * @return int $id
     */
    public function insertRow(array $data = array(), $autofill = false, array $tableDescription = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::insertRow()');
        }

        // store row in database and get id
        $this->getAdapter()->insert('Data_Tables', $data);
        $id = $this->getAdapter()->lastInsertId();

        if ($autofill) {
            // get the additional resources
            $columnResource = new Data_Model_Resource_Columns();
            $databaseResource = new Data_Model_Resource_Databases();

            // auto create entries for all columns
            $row = $databaseResource->fetchRow($data['database_id']);
            $database = $row['name'];
            $table = $data['name'];

            try {
                if(empty($tableDescription)) {
                    $descResource = new Data_Model_Resource_Description();
                    $descResource->init($database); 
                    $tableDescription = $descResource->describeTable($table);
                }

                foreach ($tableDescription['columns'] as $column) {
                    $column['table'] = $table;
                    $column['table_id'] = $id;
                    $column['database'] = $database;

                    $columnResource->insertRow($column);
                }
            } catch (Exception $e) {
                $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $id));
                throw $e;
            }
        }

        return $id;
    }

    /**
     * Deletes a table entry and all its columns.
     * @param int $id id of the row
     * @throws Exception
     * @return type 
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::deleteRow()');
        }

        $row = $this->fetchRow($id, true);

        // delete tables and columns of this database
        foreach ($row['columns'] as $column) {
            $this->getAdapter()->delete('Data_Columns', array('`id` = ?' => $column['id']));
        }
        $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $id));
    }

    /**
     * Checks whether the user can access this table
     * @param int $id id of the row
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
        if (empty($id) || empty($command)) {
            throw new Exception('$id or $command not provided in ' . get_class($this) . '::checkACL()');
        }

        $row = $this->fetchRow($id, false);
        $command = strtolower($command);

        // check if the database is published for this role
        $result = Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);

        if (($command === "select" ||
                $command === "set" ) &&
                $row['publication_select'] === "1") {

            return $result;
        } else if (($command === "alter" ||
                $command === "update" ) &&
                $row['publication_update'] === "1") {

            return $result;
        } else if (($command === "create" ||
                $command === "drop" ||
                $command === "insert" ) &&
                $row['publication_insert'] === "1") {

            return $result;
        } else {
            return false;
        }
    }

}
