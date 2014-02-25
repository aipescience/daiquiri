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
 * Resource class ...
 */
class Data_Model_Resource_Tables extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->addTables(array(
            'Data_Model_DbTable_Tables',
            'Data_Model_DbTable_Columns',
            'Data_Model_DbTable_Databases'
        ));
    }

    public function fetchId($db, $table) {
        // get the names of the involved tables
        $t = $this->getTable('Data_Model_DbTable_Tables')->getName();
        $d = $this->getTable('Data_Model_DbTable_Databases')->getName();

        // get the primary sql select object
        $select = $this->getTable()->select();
        $select = $select->from($this->getTable(),'id');
        $select->setIntegrityCheck(false);
        $select->where("`$t`.`name` = ?", trim($table));
        $select->join($d, "`$t`.`database_id` = `$d`.`id`", array('database' => 'name'));
        $select->where("`$d`.`name` = ?", trim($db));

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();
        
        if ($row) {
            return $row->id;
        } else {
            return false;
        }
    }

    /**
     * Returns a specific row from the (joined) Databases/Tables/Columns tables.
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRow($id, $fullData = true) {
        // get the names of the involved tables
        $t = $this->getTable('Data_Model_DbTable_Tables')->getName();
        $d = $this->getTable('Data_Model_DbTable_Databases')->getName();

        // get the primary sql select object
        $select = $this->getTable()->getSelect();
        $select->setIntegrityCheck(false);
        $select->where("`$t`.`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');
        $select->join($d, "`$t`.`database_id` = `$d`.`id`", array('database' => 'name'));

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();

        if ($row) {
            // get the columns for this table
            $data = $row->toArray();
            
            $data['columns'] = array();
            if ($fullData === true) {
                // get the details table
                $table = $this->getTable('Data_Model_DbTable_Columns');

                // get the sql select object
                $select = $table->select();
                $select->where('table_id = ?', $data['id']);
                $cols = $table->fetchAll($select)->toArray();

                // convert rows to flat array
                for ($j = 0; $j < count($cols); $j++) {
                    $data['columns'][] = $cols[$j];
                }
            }

            return $data;
        } else {
            return array();
        }
    }

    /**
     * Deletes a specific row from the (joined) Databases/Tables/Columns tables.
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function deleteTable($id) {
        // get the entry
        $entry = $this->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // delete columns of this table
        $resource = new Data_Model_Resource_Columns();
        if(!empty($entry['columns'])) {
            foreach ($entry['columns'] as $col) {
                $resource->deleteRow($col['id']);
            }
        }

        // delete table row
        $this->deleteRow($id);

        return false;
    }

    /**
     * Checks whether the user can access this table
     * @param int $id
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
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
