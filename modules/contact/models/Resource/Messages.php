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
 * Provides methods for accessing the Messages database table
 */
class Contact_Model_Resource_Messages extends Daiquiri_Model_Resource_Table {

    /**
     * Construtor. Sets DbTable.
     */
    public function __construct() {
        $this->addTables(array(
            'Contact_Model_DbTable_Messages',
            'Contact_Model_DbTable_Categories',
            'Contact_Model_DbTable_Status'
        ));
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRows($sqloptions = array()) {
        // get the names of the involved tables
        $m = $this->getTable('Contact_Model_DbTable_Messages')->getName();
        $c = $this->getTable('Contact_Model_DbTable_Categories')->getName();
        $s = $this->getTable('Contact_Model_DbTable_Status')->getName();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);

        // add inner joins for the category and the status
        $select->setIntegrityCheck(false);
        if (in_array('category', $sqloptions['from'])) {
            $select->join($c, "`$m`.`category_id` = `$c`.`id` ", 'category');
        }
        if (in_array('status', $sqloptions['from'])) {
            $select->join($s, "`$m`.`status_id` = `$s`.`id` ", 'status');
        }
        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select);
        return $rows->toArray();
    }

    /**
     * Returns one row specified by its id from the tables.
     * @param int $id
     * @param string $tableclass the name of the tableclass
     * @return array
     */
    public function fetchRow($id, array $from = array()) {

        $sqloptions = array();

        // get the names of the involved tables
        $messagesTableName = $this->getTable()->getName();
        $categoriesTableName = $this->getTable('Contact_Model_DbTable_Categories')->getName();
        $statusTableName = $this->getTable('Contact_Model_DbTable_Status')->getName();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`$messagesTableName`.`id` = ?", $id);

        // add inner joins for the category, the status and the user
        $select->setIntegrityCheck(false);
        $select->join($categoriesTableName, "`$messagesTableName`.`category_id` = `$categoriesTableName`.`id` ", 'category');
        $select->join($statusTableName, "`$messagesTableName`.`status_id` = `$statusTableName`.`id` ", 'status');

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select)->current();
        return $rows->toArray();
    }

}