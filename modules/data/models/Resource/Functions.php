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
    // public function fetchRows($sqloptions = array()) {
    //     $usrRoles = Daiquiri_Auth::getInstance()->getCurrentRoleParents();

    //     //roles starting at 1, therefore check for <=
    //     $select = $this->getTable()->select();
    //     $select->where('`publication_role_id` <= ?', count($usrRoles));
    //     $rows = $this->getTable()->fetchAll($select);

    //     return $rows;
    // }    

    public function fetchId($function) {
        $usrRoles = Daiquiri_Auth::getInstance()->getCurrentRoleParents();

        // get the primary sql select object
        $select = $this->getTable()->select();
        $select->where("`name` = ?", trim($function));
        $select->where("`publication_role_id` <= ?", count($usrRoles));

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

        // get the primary sql select object
        $select = $this->getTable()->getSelect();
        $select->where("`id` = ?", $id);
        $select->where("`publication_role_id` <= ?", count($usrRoles));

        $row = $this->getTable()->fetchAll($select)->current();

        $data = false;
        if ($row) {
            $data = $row->toArray();

            //get the roles
            $rolesModel = new Auth_Model_Roles();
            $roles = array_merge(array(0 => 'not published'), $rolesModel->getValues());

            if (!empty($roles[$data['publication_role_id']])) {
                $data['publication_role'] = $roles[$data['publication_role_id']];
            } else {
                $data['publication_role'] = "unknown";
            }
        }

        return $data;
    }

    /**
     * Returns the id of the function by name
     * @param string $name
     * @return array
     */
    public function fetchIdWithName($name) {
        $sqloptions = array();
        $usrRoles = Daiquiri_Auth::getInstance()->getCurrentRoleParents();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`name` = ?", trim($name));
        $select->where("`publication_role_id` <= ?", count($usrRoles));

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->toArray();

        if ($row) {
            return $row[0]['id'];
        }

        return false;
    }

    /**
     * Checks whether the user can access this function
     * @param int $id
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id) {
        $acl = Daiquiri_Auth::getInstance();

        $row = $this->fetchRow($id);

        $parentRoles = $acl->getCurrentRoleParents();

        if (in_array($row['publication_role'], $parentRoles)) {
            return true;
        }

        return false;
    }

}
