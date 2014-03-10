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

class Data_Model_Resource_Databases extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Databases');
    }

    /**
     * Fetches all database entries.
     * @return array $rows
     */
    public function fetchRows() {
        $select = $this->select();
        $select->from('Data_Databases');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches the id of one database entry specified database name.
     * @param string $db name of database
     * @return int $id
     */
    public function fetchId($db) {
        $select = $this->select();
        $select->from('Data_Databases');
        $select->where("`name` = ?", trim($db));

        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception('id not found in ' . get_class($this) . '::fetchId()');
        }

        return (int) $row['id'];
    }

    /**
     * Fetches one database entry specified by its id.
     * @param int $id id of the row
     * @param bool $tables fetch table information
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $tables = false, $columns = false) {
        // get the primary sql select object
        $select = $this->select();
        $select->from('Data_Databases');
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
            $row = $rows[0];
        }

        if ($tables === true) {
            $select = $this->select();
            $select->from('Data_Tables');
            $select->where('database_id = ?', $row['id']);
            $select->order('order ASC');
            $select->order('name ASC');

            $tables = $this->getAdapter()->fetchAll($select);

            foreach ($tables as &$table) {
                if ($columns === true) {
                    $select = $this->select();
                    $select->from('Data_Columns');
                    $select->where('table_id = ?', $table['id']);
                    $select->order('order ASC');
                    $select->order('name ASC');

                    $table['columns'] = $this->getAdapter()->fetchAll($select);
                }
            }

            $row['tables'] = $tables;
        }

        return $row;
    }

    /**
     * Deletes a database entry and all its tables and columns.
     * @param int $id id of the row
     * @throws Exception
     * @return type 
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::deleteRow()');
        }

        // get the row
        $row = $this->fetchRow($id, true, true);

        // delete tables and columns of this database
        foreach ($row['tables'] as $table) {
            foreach ($table['columns'] as $column) {
                $this->getAdapter()->delete('Data_Columns', array('`id` = ?' => $column['id']));
            }
            $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $table['id']));
        }

        // delete database row
        $this->getAdapter()->delete('Data_Databases', array('`id` = ?' => $id));
    }

    /**
     * Checks whether the user can access this database
     * @param int $id id of the row
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
        $row = $this->fetchRow($id);
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
        } else if (($command === "show tables") &&
                $row['publication_show'] === "1") {

            return $result;
        } else {
            return false;
        }
    }
}
