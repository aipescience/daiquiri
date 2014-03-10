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

class Data_Model_Resource_Functions extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Functions');
    }

    /**
     * Fetches all function entries.
     * @return array $rows
     */
    public function fetchRows() {
        $select = $this->select();
        $select->from('Data_Functions');
        $select->order('order ASC');
        $select->order('name ASC');
        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches the id of one function entry specified function name.
     * @param string $function name of function
     * @return int $id
     */
    public function fetchId($function) {
        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`name` = ?", trim($function));

        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception('id not found in ' . get_class($this) . '::fetchId()');
        }

        return (int) $row['id'];
    }

    /**
     * Fetches one function entry specified by its id.
     * @param int $id id of the row
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $columns = false) {
        $select = $this->select();
        $select->from('Data_Functions');
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

    /**
     * Checks whether the user can access this function
     * @param int $id id of the row
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id) {
        $row = $this->fetchRow($id);
        return Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);
    }

}
