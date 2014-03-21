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

class Auth_Model_Resource_Sessions extends Daiquiri_Model_Resource_Simple {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->setTablename('Auth_Sessions');
    }

    /**
     * Returns the colums of the sessions table.
     * @return array $cols
     */
    public function fetchCols() {
        return array('session', 'username', 'email', 'ip', 'userAgent', 'modified');
    }

    /**
     * Fetches a set of rows of the sessions table specified by $sqloptions.
     * @param array $sqloptions
     * @return array $rows
     */
    public function fetchRows($sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from('Auth_Sessions');
        $select->where("`data` LIKE '%Zend_Auth%'");

        $dbrows = $this->fetchAll($select);

        // loop over array to parse the database output
        $rows = array();
        foreach ($dbrows as $dbrow) {
            $match = array();

            $row['session'] = $dbrow['session'];

            if (preg_match('/s\:8\:\"username\"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow['data'], $match)) {
                $row['username'] = $match[1];
            } else {
                $row['username'] = '';
            }

            if (preg_match('/s\:5\:\"email"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow['data'], $match)) {
                $row['email'] = $match[1];
            } else {
                $row['email'] = '';
            }

            if (preg_match('/s\:2\:\"ip"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow['data'], $match)) {
                $row['ip'] = $match[1];
            } else {
                $row['ip'] = '';
            }
        
            if (preg_match('/s\:9\:\"userAgent"\;s\:[0-9]*\:\"(.*?)\"\;/', $dbrow['data'], $match)) {
                $row['userAgent'] = $match[1];
            } else {
                $row['userAgent'] = '';
            }
            
            $row['modified'] = date("Y-m-d H:i:s", $dbrow['modified']);
            
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Fetches all auth sessions for one user.
     * @param int $userId id of the user
     * @return array $rows
     */
    public function fetchAuthSessionsByUserId($userId) {
        if (empty($userId)) {
            throw new Exception('$userId not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // query the database
        $select = $this->select();
        $select->from('Auth_Sessions', array('session'));
        $select->where("`data` LIKE '%Zend_Auth%'");
        $singleQuotedUserId = $this->getAdapter()->quoteInto('?', $userId);
        $doubleQuotedUserId = str_replace("'", '"', $singleQuotedUserId);
        $select->where("`data` REGEXP 's:2:" . '"id";s:[0-9]*:' . $doubleQuotedUserId . "'");
        
        // get the rowset and convert to flat array
        $rows = array();
        foreach ($this->fetchAll($select) as $row) {
            $rows[] = $row['session'];
        }
        return $rows;
    }

    /**
     * Counts the number of sessions in the sessions table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null) {
        $select = $this->select();
        $select->from('Auth_Sessions', 'COUNT(*) as count');
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
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }
}
