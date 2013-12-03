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

class Query_Model_Resource_Examples extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setName('Query_Examples');
    }

    public function fetchRows() {
         //get the roles
        $rolesModel = new Auth_Model_Roles();
        $roles = array_merge(array(0 => 'not published'), $rolesModel->getValues());
        $role_id = Daiquiri_Auth::getInstance()->getCurrentRoleId();

        // get the primary sql select object
        $select = $this->getTable()->getSelect();

        // hide some examples if not admin
        if (!Daiquiri_Auth::getInstance()->isAdmin()) {
            $select->where("`publication_role_id` <= ? AND `publication_role_id` > 0", $role_id);
        }

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select);

        if ($rows) {
            $data = array();

            foreach($rows->toArray() as $row) {
                if (!empty($roles[$row['publication_role_id']])) {
                    $row['publication_role'] = $roles[$row['publication_role_id']];
                } else {
                    $row['publication_role'] = "unknown";
                }
                unset($row['publication_role_id']);
                $data[] = $row;
            }
            return $data;
        } else {
            return array();
        }
    }

}
