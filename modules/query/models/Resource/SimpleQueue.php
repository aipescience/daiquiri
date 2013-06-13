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

class Query_Model_Resource_SimpleQueue extends Query_Model_Resource_AbstractQueue {

    public static $needsCreateTable = true;
    public static $hasQueues = false;
    private static $status = array('success' => 1, 'error' => 2);
    public static $translations = array('id' => 'Job id',
        'user_id' => 'Internal user id',
        'username' => 'User name',
        'database' => 'Database name',
        'table' => 'Table name',
        'time' => 'Job submission time',
        'query' => 'Original query',
        'actualQuery' => 'Actual query',
        'status_id' => 'Job status',
        'status' => 'Job status',
        'tbl_size' => 'Total disk usage (in MB)',
        'tbl_idx_size' => 'Index disk usage (in MB)',
        'tbl_free' => 'Free space in table (in MB)',
        'tbl_row' => 'Approx. row count',
    );

    /**
     * Construtor. Sets table. 
     */
    public function __construct() {
        parent::__construct();

        $this->addTables(array(
            'Query_Model_DbTable_Jobs',
            'Auth_Model_DbTable_User'
        ));
    }

    /**
     * Creates a new table in the database with the given sql query.
     * 
     * SIDE EFFECT: changes $job array and fills in the missing data
     * @param array job object that hold information about the query
     * @return int status id
     */
    public function submitJob(&$job, array &$errors, $options = false) {
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        $timestamp = date("Y-m-d\TH:i:s");

        // build adapter
        $userDBResource = $this->getUserDBResource();

        // get adapter details
        $config = $userDBResource->getTable()->getAdapter()->getConfig();
        $database = $config['dbname'];
        $host = $config['host'];

        // create tablename
        $table = $job['table'];

        // check if the table already exists
        if ($this->tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        // create the actual sql statement
        $actualQuery = $job['fullActualQuery'];
        unset($job['fullActualQuery']);

        // fire up the database
        // determining the DB adapter that is used. if we have thought about that one, use direct querying
        // without using prepared statement (not that fast and uses memory)
        // if not, fall back to prepared statements querying (using adapter->query abstractions of ZEND)

        $adapter = $userDBResource->getTable()->getAdapter();
        $adaptType = get_class($adapter);

        // if query syntax is checked server side without executing query (like using paqu_validateSQL in MySQL), 
        // we just fire up the query. if not, we need to split multiline queries up and check for any exception
        // raised by the server

        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            if (strpos(strtolower($adaptType), "pdo") !== false) {
                try {
                    $stmt = $userDBResource->getTable()->getAdapter()->getConnection()->exec($actualQuery);
                } catch (Exception $e) {
                    $errors['submitError'] = $e->getMessage();
                }
            } else {
                //fallback version
                try {
                    $stmt = $userDBResource->getTable()->getAdapter()->query($actualQuery);
                } catch (Exception $e) {
                    $errors['submitError'] = $e->getMessage();
                }

                $stmt->closeCursor();
            }
        } else {
            // split the query into multiple queries...
            $processing = new Query_Model_Resource_Processing();

            $multiLine = $processing->splitQueryIntoMultiline($actualQuery, $errors);

            foreach ($multiLine as $query) {
                if (strpos(strtolower($adaptType), "pdo") !== false) {
                    try {
                        $stmt = $userDBResource->getTable()->getAdapter()->getConnection()->exec($query);
                    } catch (Exception $e) {
                        $errors['submitError'] = $e->getMessage();
                        break;
                    }
                } else {
                    try {
                        $stmt = $userDBResource->getTable()->getAdapter()->query($query);
                    } catch (Exception $e) {
                        $errors['submitError'] = $e->getMessage();
                        break;
                    }
                }
            }

            if (strpos(strtolower($adaptType), "pdo") === false) {
                $stmt->closeCursor();
            }
        }

        // if error has been raised just report it and don't add a job
        if (!empty($errors)) {
            return Query_Model_Resource_SimpleQueue::$status['error'];
        }

        // check if it worked
        if (in_array($table, $userDBResource->getTable()->getAdapter()->listTables())) {
            // set status
            $statusId = Query_Model_Resource_SimpleQueue::$status['success'];
        } else {
            $statusId = Query_Model_Resource_SimpleQueue::$status['error'];
        }

        if (!empty($options) && array_key_exists('jobId', $options)) {
            $job['id'] = "{$options['jobId']}";
        }

        $job['database'] = $database;
        $job['host'] = $host;
        $job['user_id'] = $userId;
        $job['status_id'] = $statusId;
        $job['time'] = $timestamp;

        $this->createJob($job);

        return $statusId;
    }

    /**
     * Create a new job.
     * @param array $param
     */
    public function createJob(array $params) {
        // store job in database
        $this->insertRow($params);
    }

    /**
     * Given a table name, check if it already exists in the user database
     * @param table name
     * @return true or false
     */
    public function tableExists($table) {
        //build adapter
        $userDBResource = $this->getUserDBResource();

        $sql = "SHOW TABLES LIKE '{$table}';";

        try {
            $stmt = $userDBResource->getTable()->getAdapter()->query($sql);
        } catch (Exception $e) {
            //check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }

        $rows = $stmt->fetchAll();

        if (sizeof($rows) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Rename table of a job with given id.
     * @param array $id
     * @param new table name
     * @return status
     */
    public function renameJob($id, $newTable) {
        // get job from the database
        $job = $this->fetchRow($id);

        //check if the table already exists
        if ($this->tableExists($newTable)) {
            return "Table '{$newTable}' already exists";
        }

        // rename result table for job
        $this->renameTable($job['database'], $job['table'], $newTable);

        //Updating the job entry
        $table = $this->getTable('Query_Model_DbTable_Jobs');
        $where = $table->getAdapter()->quoteInto('id = ?', $id);
        $table->update(array('table' => $newTable), $where);

        return 'ok';
    }

    /**
     * Delete a job and the corresponding table.
     * @param array $param
     */
    public function removeJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        // drop result table for job
        $this->dropTable($job['database'], $job['table']);

        // remove job from job table
        $this->deleteRow($id);

        return 'ok';
    }

    /**
     * Kills a running job. Not supported by this queue type!
     * @param array $param
     */
    public function killJob($id) {
        //kill is not supported by this queue... thus do nothing
        return 'ok';
    }

    /**
     * Returns true if given status is killable and false, if job cannot be killed
     * @param string $status
     * @return bool
     */
    public function isStatusKillable($status) {
        return false;
    }

    /**
     * Return job status.
     * @param type $input id OR name of the job
     */
    public function fetchJobStatus($id) {
        $row = $this->fetchRow($id);
        return $row['status_id'];
    }

    /**
     * Returns an array with a configured DB query request . This function must 
     * ensure that a row with a job is returned as such: 0: id, 1: name/table name, 2: status
     * @param int $userId
     * @return array
     */
    public function getSQLOptionsForIndex($userId) {
        $w = $this->getTable()->getAdapter()->quoteInto('`user_id` = ?', $userId);
        return array(
            'from' => array('id', 'table', 'status'),
            'limit' => NULL,
            'start' => 0,
            'where' => array($w)
        );
    }

    /**
     * Returns the columns of the (joined) tables.
     * @return array 
     */
    public function fetchCols() {
        $cols = $this->getTable()->getCols();
        $cols[] = 'status';
        $cols[] = 'username';
        unset($cols[array_search('status_id', $cols)]);
        unset($cols[array_search('user_id', $cols)]);
        return $cols;
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRows($sqloptions = array()) {
        // get the names of the involved tables
        $j = $this->getTable('Query_Model_DbTable_Jobs')->getName();
        $u = $this->getTable('Auth_Model_DbTable_User')->getName();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);

        // add inner joins for the category and the status
        $select->setIntegrityCheck(false);
        if (is_array($sqloptions['from']) && in_array('username', $sqloptions['from'])) {
            $select->join($u, "`$j`.`user_id` = `$u`.`id` ", 'username');
        }
        if (is_array($sqloptions['from']) && in_array('status', $sqloptions['from'])) {
            $select->columns('status_id');
        }

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select);

        $result = $rows->toArray();

        //go through the result set and replace all instances of status with string
        if (!empty($result) && array_key_exists('status_id', $result[0]) &&
                in_array('status', $sqloptions['from'])) {

            $statusStrings = array_flip(Query_Model_Resource_SimpleQueue::$status);
            foreach ($result as &$row) {
                $row['status'] = $statusStrings[$row['status_id']];

                if (in_array('status', $sqloptions['from']) && !in_array('status_id', $sqloptions['from'])) {
                    unset($row['status_id']);
                }
            }
        }

        return $result;
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRow($id) {
        // get the names of the involved tables
        $j = $this->getTable('Query_Model_DbTable_Jobs')->getName();

        // get the primary sql select object
        $select = $this->getTable()->select();
        $select->setIntegrityCheck(false);
        $select->from($this->getTable(), array('id', 'database', 'table', 'time', 'query', 'actualQuery', 'status_id', 'user_id'));
        $select->where($j . '.id = ?', $id);

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select)->current();

        if ($rows) {
            $result = $rows->toArray();
        } else {
            $result = array();
        }

        // go through the result set and replace all instances of status with string
        if (!empty($result)) {
            $statusStrings = array_flip(Query_Model_Resource_SimpleQueue::$status);
            $result['status'] = $statusStrings[$result['status_id']];
            unset($result['status_id']);
        }

        return $result;
    }

    /**
     * Rename a given table WITHOUT ACL check.
     * @param type $input id OR name of the job
     * @param table name
     * @param new table name
     */
    private function renameTable($db, $table, $newTable) {
        //due to extremely broken Zend_Db::quoteInto, this looks horrible
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);

        $sql = "RENAME TABLE ";
        $sql .= $adapter->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $adapter->quoteIdentifier($table);
        $sql .= " TO ";
        $sql .= $adapter->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $adapter->quoteIdentifier($newTable);
        $sql .= ";";

        try {
            $adapter->query($sql)->closeCursor();
        } catch (Exception $e) {
            //check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }
    }

    /**
     * Drop a given table WITHOUT ACL check.
     * @param type $input id OR name of the job
     */
    private function dropTable($db, $table) {
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);

        //due to extremely broken Zend_Db::quoteInto, this looks horrible
        $sql = "DROP TABLE ";
        $sql .= $adapter->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $adapter->quoteIdentifier($table);
        $sql .= ";";

        try {
            $adapter->query($sql)->closeCursor();
        } catch (Exception $e) {
            //check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }
    }

    /**
     * Returns statistical information about the database table corresponding to
     * the job id if exists.
     * @param int id
     * @return array 
     */
    public function fetchTableStats($id) {
        //first obtain information about this job
        $row = $this->fetchRow($id);

        //only get statistics if the job finished
        if ($row['status'] === "success") {
            return array();
        }

        //check if this table is locked and if yes, don't query information_schema. This will result in a
        //"waiting for metadata lock" and makes daiquiri hang
        $adapter = $this->getUserDBResource()->getTable()->getAdapter();
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $row['database'] . '` WHERE In_use > 0')->fetchAll();

        $found = false;
        foreach ($lockedTables as $table) {
            if ($table['Table'] === $row['table']) {
                $found = true;
                break;
            }
        }

        if ($found === false) {
            //check if table is available
            if (!in_array($row['table'], $this->getUserDBResource()->getTable()->getAdapter()->listTables())) {
                return array();
            }

            //obtain row count
            //obtain table size in MB
            //obtain index size in MB
            //obtain free space (in table) in MB
            $sql = "SELECT round( (data_length + index_length) / 1024 / 1024, 3 ) AS 'tbl_size', " .
                    "round( index_length / 1024 / 1024, 3) AS 'tbl_idx_size', " .
                    "round( data_free / 1024 / 1024, 3 ) AS 'tbl_free', table_rows AS 'tbl_row' " .
                    "FROM information_schema.tables " .
                    "WHERE table_schema = ? AND table_name = ?;";

            $stmt = $this->getUserDBResource()->getTable()->getAdapter()->query($sql, array($row['database'], $row['table']));

            $row = $stmt->fetch();
        } else {
            $row = array();
        }

        return $row;
    }

    /**
     * Returns the translation array for the columns in show jobs.
     * @return array 
     */
    public function getTranslations() {
        return Query_Model_Resource_SimpleQueue::$translations;
    }

}
