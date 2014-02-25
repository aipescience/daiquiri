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
class Data_Model_Resource_Functions extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->setTable('Data_Model_DbTable_Functions');
    }

    /**
     * Returns all functions that user has access permission
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRows($sqloptions = array()) {
        // roles starting at 1, therefore check for <=
        $select = $this->getTable()->select();
        $rows = $this->getTable()->fetchAll($select);

        return $rows;
    }

    public function fetchId($function) {
        // get the primary sql select object
        $select = $this->getTable()->select();
        $select->where("`name` = ?", trim($function));

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
        // get the primary sql select object
        $select = $this->getTable()->getSelect();
        $select->where("`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        $row = $this->getTable()->fetchAll($select)->current();

        if ($row) {
            return $row->toArray();
        } else {
            return array();
        }
    }

    /**
     * Checks whether the user can access this function
     * @param int $id
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id) {
        $row = $this->fetchRow($id);
        return Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);
    }

}
