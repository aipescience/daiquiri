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
  
    public function fetchRows($sqloptions = array(), $tableclass = null) {
        // get the primary table
        $table = $this->getTable($tableclass);

        // get select object
        $select = $table->getSelect($sqloptions);
        $select->order('order ASC');
        $select->order('name ASC');

        // get result convert to array and return
        return $table->fetchAll($select)->toArray();
    }

    public function fetchId($db) {
        // get the primary sql select object
        $select = $this->getTable()->select();
        $select->where("`name` = ?", trim($db));

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
    public function fetchRow($id, $fullData = false) {
        // get the primary sql select object
        $select = $this->getTable()->getSelect();
        $select->where("`id` = ?", $id);

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();

        if ($row) {
            $data = $row->toArray();
            $data['tables'] = array();

            if ($fullData === true) {
                // get the details table
                $tablesTable = $this->getTable('Data_Model_DbTable_Tables');

                // get the sql select object
                $select = $tablesTable->select();
                $select->where('database_id = ?', $data['id']);
                $select->order('order ASC');
                $select->order('name ASC');
                $tables = $tablesTable->fetchAll($select)->toArray();

                // get columns table
                $columnsTable = $this->getTable('Data_Model_DbTable_Columns');

                // convert rows to flat array
                for ($i = 0; $i < count($tables); $i++) {
                    $table = $tables[$i];
                    unset($table['database_id']);

                    $table['columns'] = array();

                    // get colums for table
                    $select = $columnsTable->select();
                    $select->where('table_id = ?', $table['id']);
                    $select->order('order ASC');
                    $select->order('name ASC');
                    $cols = $columnsTable->fetchAll($select)->toArray();

                    for ($j = 0; $j < count($cols); $j++) {
                        unset($cols[$j]['database_id']);
                        unset($cols[$j]['table_id']);
                        $table['columns'][] = $cols[$j];
                    }

                    $data['tables'][] = $table;
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

    /**
     * Checks whether the user can access this database
     * @param int $id
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
