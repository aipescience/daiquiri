<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Auth_Model_Resource_Sessions extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Sessions');
    }

    /**
     * Returns the colums of the sessions table.
     * @return array $cols
     */
    // public function fetchCols() {
    //     $cols = parent::fetchCols();
    //     Zend_Debug::dump($cols); die(0);
    //     return array('session', 'username', 'email', 'ip', 'userAgent', 'modified');
    // }

    /**
     * Fetches a set of rows of the sessions table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
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
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
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
