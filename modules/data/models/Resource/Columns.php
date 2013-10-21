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
class Data_Model_Resource_Columns extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->addTables(array(
            'Data_Model_DbTable_Columns',
            'Data_Model_DbTable_Tables',
            'Data_Model_DbTable_Databases'
        ));
    }

    public function fetchId($db, $table, $column) {
        $usrRoles = Daiquiri_Auth::getInstance()->getCurrentRoleParents();

        // get the names of the involved tables
        $c = $this->getTable('Data_Model_DbTable_Columns')->getName();
        $t = $this->getTable('Data_Model_DbTable_Tables')->getName();
        $d = $this->getTable('Data_Model_DbTable_Databases')->getName();

        // get the primary sql select object
        $select = $this->getTable()->select();
        $select = $select->from($this->getTable(),'id');
        $select->setIntegrityCheck(false);
        $select->where("`$c`.`name` = ?", trim($column));
        $select->join($t, "`$c`.`table_id` = `$t`.`id`", array('tablename' => 'name'));
        $select->where("`$t`.`name` = ?", trim($table));
        $select->where("`$t`.`publication_role_id` <= ?", count($usrRoles));
        $select->join($d, "`$t`.`database_id` = `$d`.`id`", array('dbname' => 'name'));
        $select->where("`$d`.`name` = ?", trim($db));
        $select->where("`$d`.`publication_role_id` <= ?", count($usrRoles));

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
    public function fetchRow($id) {
        $usrRoles = Daiquiri_Auth::getInstance()->getCurrentRoleParents();

        // get the names of the involved tables
        $c = $this->getTable('Data_Model_DbTable_Columns')->getName();
        $t = $this->getTable('Data_Model_DbTable_Tables')->getName();
        $d = $this->getTable('Data_Model_DbTable_Databases')->getName();

        // get the primary sql select object
        $select = $this->getTable()->getSelect();
        $select->where("`$c`.`id` = ?", $id);

        // add inner joins for the category, the status and the user
        $select->setIntegrityCheck(false);
        $select->join($t, "`$c`.`table_id` = `$t`.`id`", array('table' => 'name'));
        $select->join($d, "`$t`.`database_id` = `$d`.`id`", array('database' => 'name'));
        $select->where("`$t`.`publication_role_id` <= ?", count($usrRoles));
        $select->where("`$d`.`publication_role_id` <= ?", count($usrRoles));

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();

        $data = false;

        if($row) {
            $row = $row->toArray();
            unset($row['database_id']);
            unset($row['table_id']);

            $data = $row;
        }

        return $data;
    }
}
