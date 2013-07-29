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
class Data_Model_Resource_Databases extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->addTables(array(
            'Data_Model_DbTable_Databases',
            'Data_Model_DbTable_Columns',
            'Data_Model_DbTable_Tables'
        ));
    }

    /**
     * Returns the proper columns of the database entries.
     * @return array 
     */
    public function fetchCols() {
        return array('id', 'name', 'info', 'adapter', 'publication_role_id',
            'publication_select', 'publication_update', 'publication_insert',
            'publication_show', 'tables');
    }

    /**
     * Returns a specific row from the (joined) Databases/Tables/Columns tables.
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRow($id, $fullData = true) {
        $sqloptions = array();

        //get the roles
        $rolesModel = new Auth_Model_Roles();
        $roles = array_merge(array(0 => 'not published'), $rolesModel->getValues());

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`id` = ?", $id);

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();

        $data = false;

        if ($row) {
            // get the columns for this table
            $data = $row->toArray();

            if (!empty($roles[$data['publication_role_id']])) {
                $data['publication_role'] = $roles[$data['publication_role_id']];
            } else {
                $data['publication_role'] = "unknown";
            }
            $data['tables'] = array();

            if ($fullData === true) {
                // get the details table
                $tablesTable = $this->getTable('Data_Model_DbTable_Tables');

                // get the sql select object
                $select = $tablesTable->select();
                $select->where('database_id = ?', $data['id']);
                $tables = $tablesTable->fetchAll($select)->toArray();

                // get columns table
                $columnsTable = $this->getTable('Data_Model_DbTable_Columns');

                // convert rows to flat array
                for ($i = 0; $i < count($tables); $i++) {
                    $table = $tables[$i];
                    unset($table['database_id']);

                    if (!empty($roles[$table['publication_role_id']])) {
                        $table['publication_role'] = $roles[$table['publication_role_id']];
                    } else {
                        $table['publication_role'] = "unknown";
                    }

                    $table['columns'] = array();

                    // get colums for table
                    $select = $columnsTable->select();
                    $select->where('table_id = ?', $table['id']);
                    $cols = $columnsTable->fetchAll($select)->toArray();

                    for ($j = 0; $j < count($cols); $j++) {
                        unset($cols[$j]['database_id']);
                        unset($cols[$j]['table_id']);
                        $table['columns'][] = $cols[$j];
                    }

                    $data['tables'][] = $table;
                }
            }
        }

        return $data;
    }

    /**
     * Returns the id of the database with the given name
     * @param string $name
     * @return array
     */
    public function fetchIdWithName($name) {
        $sqloptions = array();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`name` = ?", $name);

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->toArray();

        if ($row) {
            return $row[0]['id'];
        }

        return false;
    }

    /**
     * Checks whether the user can access this database
     * @param int $id
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
        $acl = Daiquiri_Auth::getInstance();

        $row = $this->fetchRow($id);
        $command = strtolower($command);

        if (($command === "select" ||
                $command === "set" ) &&
                $row['publication_select'] === "1") {

            $parentRoles = $acl->getCurrentRoleParents();

            if (in_array($row['publication_role'], $parentRoles)) {
                return true;
            }
        }

        if (($command === "alter" ||
                $command === "update" ) &&
                $row['publication_update'] === "1") {

            $parentRoles = $acl->getCurrentRoleParents();

            if (in_array($row['publication_role'], $parentRoles)) {
                return true;
            }
        }

        if (($command === "create" ||
                $command === "drop" ||
                $command === "insert" ) &&
                $row['publication_insert'] === "1") {

            $parentRoles = $acl->getCurrentRoleParents();

            if (in_array($row['publication_role'], $parentRoles)) {
                return true;
            }
        }

        if (($command === "show tables") &&
                $row['publication_show'] === "1") {

            $parentRoles = $acl->getCurrentRoleParents();

            if (in_array($row['publication_role'], $parentRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes a specific row from the (joined) Databases/Tables/Columns tables.
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function deleteDatabase($id) {
        // get the entry
        $entry = $this->fetchRow($id);

        // delete tables and columns of this database
        $tablesResource = new Data_Model_Resource_Tables();
        if(!empty($entry['tables'])) {
            foreach ($entry['tables'] as $table) {
                $tablesResource->deleteTable($table['id']);
            }
        }

        // delete database row
        $this->deleteRow($id);

        return false;
    }

}
