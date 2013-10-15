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
 * Resource class for the application management.
 */
class Auth_Model_Resource_Sessions extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->setTable('Auth_Model_DbTable_Sessions');
    }

    public function fetchCols() {
        return array('session', 'username', 'email', 'ip', 'userAgent', 'modified');
    }

    public function fetchRows($sqloptions = array()) {
        // get select object
        $sqloptions['from'][] = 'data';
        $select = $this->getTable()->getSelect();
        $select->where("`data` LIKE '%Zend_Auth%'");

        // get result convert to array
        $rows = array();
        foreach ($this->getTable()->fetchAll($select) as $dbrow) {
            $match = array();
            if (in_array("session", $sqloptions['from'])) {
                $row['session'] = $dbrow->session;
            }
            if (in_array("username", $sqloptions['from'])) {
                if (preg_match('/s\:8\:\"username\"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow->data, $match)) {
                    $row['username'] = $match[1];
                } else {
                    $row['username'] = '';
                }
            }
            if (in_array("email", $sqloptions['from'])) {
                if (preg_match('/s\:5\:\"email"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow->data, $match)) {
                    $row['email'] = $match[1];
                } else {
                    $row['email'] = '';
                }
            }
            if (in_array("ip", $sqloptions['from'])) {
                if (preg_match('/s\:2\:\"ip"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow->data, $match)) {
                    $row['ip'] = $match[1];
                } else {
                    $row['ip'] = '';
                }
            }
            if (in_array("userAgent", $sqloptions['from'])) {
                if (preg_match('/s\:9\:\"userAgent"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow->data, $match)) {
                    $row['userAgent'] = $match[1];
                } else {
                    $row['userAgent'] = '';
                }
            }
            if (in_array("modified", $sqloptions['from'])) {
                $row['modified'] = date("Y-m-d H:i:s", $dbrow->modified);
            }

            $rows[] = $row;
        }

        // get result convert to array and return
        return $rows;
    }

    public function fetchAuthSessionsByUserId($userId) {
        // check input
        if ($userId === null) {
            throw new Exception('$id is no int or missing');
        }

        // query the database
        $select = $this->getTable()->select()->where("`data` LIKE '%Zend_Auth%'");
        $singleQuotedUserId = $this->getTable()->getAdapter()->quoteInto('?', $userId);
        $doubleQuotedUserId = str_replace("'", '"', $singleQuotedUserId);
        $select->where("`data` REGEXP 's:2:" . '"id";s:[0-9]*:' . $doubleQuotedUserId . "'");
        $select->from($this->getTable(), array('session'));

        // get the rowset and convert to flat array
        $sessions = array();
        foreach ($this->getTable()->fetchAll($select) as $row) {
            $sessions[] = $row->session;
        }

        return $sessions;
    }

    public function countRows(array $sqloptions = null, $tableclass = null) {
        //get the table
        $table = $this->getTable($tableclass);

        // create selcet object
        $select = $table->select();
        $select->from($table, 'COUNT(*) as count');
        $select->where("`data` LIKE '%Zend_Auth%'");
        
        if ($sqloptions) {
            if (isset($sqloptions['where'])) {
                foreach ($sqloptions['where'] as $w) {
                    $select = $select->where($w);
                }
            }
            if (isset($sqloptions['orWhere'])) {
                foreach ($sqloptions['orWhere'] as $w) {
                    $select = $select->orWhere($w);
                }
            }
        }

        // query database and return
        return (int) $table->fetchRow($select)->count;
    }
}
