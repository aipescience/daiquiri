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

class Data_Model_Resource_Functions extends Daiquiri_Model_Resource_Table {

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
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Data_Functions');
        $select->order('order ASC');
        $select->order('name ASC');
        
        return $this->fetchAll($select);
    }

    /**
     * Fetches the id of one function entry specified function name.
     * @param string $function name of function
     * @throws Exception
     * @return int $id
     */
    public function fetchIdByName($function) {
        if (empty($function)) {
            throw new Exception('$function not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`name` = ?", trim($function));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return (int) $row['id'];
        }
    }

    /**
     * Fetches one function entry specified by its id.
     * @param int $id id of the row
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchOne($select);
    }

    /**
     * Checks whether the user can access this function
     * @param int $id id of the row
     * @param int $role
     * @param string $command SQL command
     * @throws Exception
     * @return array
     */
    public function checkACL($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $row = $this->fetchRow($id);
        return Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);
    }

}
