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

class Contact_Model_Resource_Messages extends Daiquiri_Model_Resource_Simple {

    public function __construct() {
        $this->setTablename('Contact_Messages');
    }

    public function fetchRows($sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());
        $select->join('Contact_Categories','Contact_Categories.id = Contact_Messages.category_id','category');
        $select->join('Contact_Status','Contact_Status.id = Contact_Messages.status_id','status');

        return $this->getAdapter()->fetchAll($select);
    }

    public function fetchRow($id, array $from = array()) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        $select = $this->select();
        $select->from('Contact_Messages');
        $select->where('Contact_Messages.id = ?', $id);
        $select->join('Contact_Categories','Contact_Categories.id = Contact_Messages.category_id','category');
        $select->join('Contact_Status','Contact_Status.id = Contact_Messages.status_id','status');

        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception($id . ' not found in ' . get_class($this) . '::fetchRow()');
        }

        return $row;
    }

}