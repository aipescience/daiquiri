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

class Query_Model_Resource_PaquQQueue extends Query_Model_Resource_AbstractQueue {

    public static $needsCreateTable = true;
    public static $hasQueues = true;
    private static $status = array('pending' => 0, 'running' => 1, 'removed' => 2, 'error' => 3, 'success' => 4, 'timeout' => 5, 'killed' => 6);
    private static $translations = array('id' => 'Job id',
        'user_id' => 'Internal user id',
        'username' => 'User name',
        'database' => 'Database name',
        'table' => 'Table name',
        'timeSubmit' => 'Job submission time',
        'timeExecute' => 'Job execution time',
        'timeFinish' => 'Job ending time',
        'query' => 'Original query',
        'actualQuery' => 'Actual query',
        'queue' => 'Queue',
        'status' => 'Job status',
        'error' => 'Error message',
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
            'Query_Model_DbTable_JobsPaquQQueue',
            'Query_Model_DbTable_JobsHistoryPaquQQueue',
            'Query_Model_DbTable_QueuesPaquQQueue',
            'Query_Model_DbTable_UserGroupsPaquQQueue',
            'Daiquiri_Model_DbTable_Simple',
            'Auth_Model_DbTable_User'
        ));

        //setting adapter of qqueue tables to user db adapter
        $userDBResource = $this->getUserDBResource();

        $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->setAdapter($userDBResource->getTable()->getAdapter());
        $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue')->setAdapter($userDBResource->getTable()->getAdapter());
        $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->setAdapter($userDBResource->getTable()->getAdapter());
        $this->getTable('Query_Model_DbTable_UserGroupsPaquQQueue')->setAdapter($userDBResource->getTable()->getAdapter());
        $this->getTable('Daiquiri_Model_DbTable_Simple')->setAdapter($userDBResource->getTable()->getAdapter());
    }

    /**
     * Creates a new table in the database with the given sql query.
     * @param array job object that hold information about the query
     * @return int status id
     */
    public function submitJob(&$job, array &$errors, $options = false) {
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        //are we guest?
        if ($userId === null) {
            $userId = 0;
        }

        $timestamp = date("Y-m-d\TH:i:s");

        // create tablename
        $table = $job['table'];

        $userDBResource = $this->getUserDBResource();

        // get adapter details
        $config = $userDBResource->getTable()->getAdapter()->getConfig();
        $database = $config['dbname'];

        //check if the table already exists
        if ($this->tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        // create the actual sql statement
        $query = $job['query'];
        $planQuery = $job['actualQuery'];
        $actualQuery = $job['fullActualQuery'];
        unset($job['fullActualQuery']);

        //create the job submission query
        if (empty($options) || !array_key_exists('queue', $options)) {
            $queue = Daiquiri_Config::getInstance()->query->queue->qqueue->defaultQueue;
        } else {
            //check if queue exists on server, if not use default
            $queues = $this->fetchQueues();

            $queue = Daiquiri_Config::getInstance()->query->queue->qqueue->defaultQueue;
            foreach ($queues as $currQueue) {
                if ($currQueue['name'] === $options['queue']) {
                    $queue = $options['queue'];
                }
            }
        }

        if (empty($options) || !array_key_exists('usrGrp', $options)) {
            $userGroup = Daiquiri_Config::getInstance()->query->queue->qqueue->defaultUsrGrp;
        } else {
            //check if user group exists on server, if not use default.
            $groups = $this->fetchUserGroups();

            $userGroup = Daiquiri_Config::getInstance()->query->queue->qqueue->defaultUsrGrp;
            foreach ($groups as $group) {
                if ($group['name'] === $options['usrGrp']) {
                    $userGroup = $options['usrGrp'];
                }
            }
        }

        $jobId = "NULL";
        if (!empty($options) && array_key_exists('jobId', $options)) {
            $jobId = "{$options['jobId']}";
        }

        //if the plan query is not the same as the actual query, this means this is run in the context
        //of paqu and we are going to hide the actual query from the user. We therefore add the plan query
        //to the end of the user given query and comment it out, for reference.
        if ($planQuery !== $actualQuery) {
            //split $planQuery into lines
            $query .= "\n-- The query plan used to run this query: --";
            $query .= "\n--------------------------------------------\n--\n";

            foreach ($planQuery as $line) {
                $query .= "-- " . $line . "\n";
            }
        }

        $jobSubmissionQuery = "SELECT qqueue_addJob({$jobId}, {$userId}, '{$userGroup}', '{$queue}', " .
                $this->getTable()->getAdapter()->quote($actualQuery) .
                ", '{$database}', '{$table}', NULL, 1, " .
                $this->getTable()->getAdapter()->quote($query) . ");";

        // fire up the database
        try {
            $result = $this->plainQuery($jobSubmissionQuery);
        } catch (Exception $e) {
            $errors['submitError'] = $e->getMessage();
            return Query_Model_Resource_PaquQQueue::$status['error'];
        }

        return $result;
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

        //first check for pending jobs that might already write to the requested table (bug #209)
        //get adapter details
        $config = $userDBResource->getTable()->getAdapter()->getConfig();
        $database = $config['dbname'];

        $j = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->getName();

        $sqloptions = array();
        $sqloptions['from'] = array('id', 'user_id' => 'usrId', 'username' => 'usrId', 'database' => 'resultDBName', 'table' => 'resultTableName', 'timeSubmit',
            'timeExecute', 'timeFinish', 'query', 'actualQuery', 'queue', 'status', 'error');
        $sqloptions['where'] = array();

        $selectPending = $this->_createJobSelect($sqloptions);
        $selectPending->where($j . '.status != 2');
        $selectPending->where($j . '.resultTableName = ?', $table);
        $selectPending->where($j . '.resultDBName = ?', $database);

        $rows = $this->getTable()->fetchAll($selectPending);

        if (count($rows) !== 0) {
            return true;
        }

        //then check if table is already there...
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

        //check if we really can rename this job
        if ($job['status'] != 'pending' &&
                $job['status'] != 'running') {

            $this->_renameTable($job['database'], $job['table'], $newTable);

            //Updating the job entry
            $table = $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue');
            $where = $table->getAdapter()->quoteInto('id = ?', $id);
            $table->update(array('resultTableName' => $newTable), $where);

            return 'ok';
        }
    }

    /**
     * Delete a job and the corresponding table. CHECK ACLs!!
     * @param array $param
     * @return status string
     */
    public function removeJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        //check if we need to kill the query first
        if ($job['status'] == 'pending' ||
                $job['status'] == 'running') {
            $this->killJob($id);

            return 'ok';
        }

        // drop result table for job
        $this->_dropTable($job['database'], $job['table']);

        //Updating the job entry
        $table = $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue');
        $where = $table->getAdapter()->quoteInto('id = ?', $id);
        //$table->delete($where);
        $table->update(array('status' => Query_Model_Resource_PaquQQueue::$status['removed']), $where);

        return 'ok';
    }

    /**
     * Kills a running job. 
     * @param array $param
     */
    public function killJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        if (empty($job) || !($job['status'] == Query_Model_Resource_PaquQQueue::$status['pending'] ||
                $job['status'] == Query_Model_Resource_PaquQQueue::$status['running'])) {
            return 'error';
        }

        $jobKillQuery = "SELECT qqueue_killJob({$job['id']});";

        // fire up the database
        try {
            $result = $this->plainQuery($jobKillQuery);
        } catch (Exception $e) {
            //ignore errors here
        }

        return 'ok';
    }

    /**
     * Returns true if given status is killable and false, if job cannot be killed
     * @param string $status
     * @return bool
     */
    public function isStatusKillable($status) {
        if (($status === "pending") || ($status === "running"))
            return true;
        else
            return false;
    }

    /**
     * Return job status.
     * @param type $input id OR name of the job
     */
    public function fetchJobStatus($id) {
        $row = $this->fetchRow($id);
        return $row['status'];
    }

    /**
     * Returns an array with a configured DB query request . This function must 
     * ensure that a row with a job is returned as such: 0: id, 1: name/table name, 2: status
     * @param int $userId
     * @return array
     */
    public function getSQLOptionsForIndex($userId) {
        // get the users private database
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapterConfig($username, '');

        $sqloptions = array();

        $sqloptions['from'] = array('id', 'table', 'status', 'timeSubmit');
        $sqloptions['limit'] = NULL;
        $sqloptions['start'] = 0;
        $sqloptions['order'] = array("table DESC");
        $sqloptions['where'] = array("mysqlUserName = '{$adapter['username']}'", "resultDBName = '{$db}'", "status != " . Query_Model_Resource_PaquQQueue::$status['removed']);

        return $sqloptions;
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRows($sqloptions = array()) {
        $select = $this->_createJobTableUnion($sqloptions);

        $rows = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->fetchAll($select);

        $result = $rows->toArray();

        $this->_resolveUserName($result, $sqloptions);
        $this->_resolveStatus($result, $sqloptions);
        $this->_resolveQueues($result, $sqloptions);
        $this->_cleanPaquQueries($result, $sqloptions);

        return $result;
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRow($id) {
        // get the names of the involved tables
        $h = $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue')->getName();
        $j = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->getName();

        $sqloptions = array();
        $sqloptions['from'] = array('id', 'user_id' => 'usrId', 'username' => 'usrId', 'database' => 'resultDBName', 'table' => 'resultTableName', 'timeSubmit',
            'timeExecute', 'timeFinish', 'query', 'actualQuery', 'queue', 'status', 'error');
        $sqloptions['where'] = array();

        $selectPending = $this->_createJobSelect($sqloptions);
        $selectPending->where($j . '.id = ?', $id);

        $selectHistory = $this->_createJobHistorySelect($sqloptions);
        $selectHistory->where($h . '.id = ?', $id);

        //@TODO: Check ACL if user is actually allowed to access this id!

        $select = $this->getTable()->select()->union(array($selectPending, $selectHistory));

        // get the rowset and return
        $rows = $this->getTable()->fetchAll($select)->current();
        if (!empty($rows)) {
            $result = array($rows->toArray());
        } else {
            $result[0] = array();
        }

        $this->_resolveUserName($result, $sqloptions);
        $this->_resolveStatus($result, $sqloptions);
        $this->_resolveQueues($result, $sqloptions);
        $this->_cleanPaquQueries($result, $sqloptions);

        return $result[0];
    }

    /**
     * Returns the columns of the (joined) tables.
     * @return array 
     */
    public function fetchCols() {
        $cols = $this->getTable()->getCols();
        $cols[] = 'username';
        unset($cols[array_search('usrId', $cols)]);

        return $cols;
    }

    /**
     * Returns the number of rows from the (joined) Auth tables specified by $where.
     * @param array $where
     * @param string $tableclass
     * @return int 
     */
    public function countRows(array $sqloptions = null, $tableclass = null) {
        $sqloptions2 = array();
        $sqloptions2['from'] = array("id");
        $sqloptions2['where'] = $sqloptions['where'];
        $sqloptions2['orWhere'] = $sqloptions['orWhere'];

        $selectPending = $this->_createJobSelect($sqloptions2);
        $selectHistory = $this->_createJobHistorySelect($sqloptions2);

        $selectPending->columns('COUNT(*) as count');
        $selectHistory->columns('COUNT(*) as count');

        $count = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->fetchRow($selectPending)->count +
                $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue')->fetchRow($selectHistory)->count;

        return (int) $count;
    }

    public function fetchQueues() {
        $sqloptions = array();
        $sqloptions['from'] = array("id", "name", "priority", "timeout");

        $select = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->getSelect($sqloptions);
        $rows = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->getAdapter()->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function fetchQueue($id) {
        $sqloptions = array();
        $sqloptions['from'] = array("id", "name", "priority", "timeout");

        $select = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->select();
        $select->where("id = ?", $id);
        $rows = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->fetchAll($select);

        return $rows;
    }

    public function fetchDefaultQueue() {
        $sqloptions = array();
        $sqloptions['from'] = array("id", "name", "priority", "timeout");

        if(empty(Daiquiri_Config::getInstance()->query->queue->qqueue->defaultQueue)) {
            throw new Exception("PaquQQueue: No default queue defined");
        }

        $select = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->select();
        $select->where("name = ?", Daiquiri_Config::getInstance()->query->queue->qqueue->defaultQueue);
        $rows = $this->getTable('Query_Model_DbTable_QueuesPaquQQueue')->fetchAll($select);

        if (count($rows) === 0) {
            return array();
        }

        return $rows;
    }

    public function fetchUserGroups() {
        $sqloptions = array();
        $sqloptions['from'] = array("id", "name", "priority");

        $select = $this->getTable('Query_Model_DbTable_UserGroupsPaquQQueue')->getSelect($sqloptions);
        $rows = $this->getTable('Query_Model_DbTable_UserGroupsPaquQQueue')->getAdapter()->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function fetchUserGroup($id) {
        $sqloptions = array();
        $sqloptions['from'] = array("id", "name", "priority");

        $select = $this->getTable('Query_Model_DbTable_UserGroupsPaquQQueue')->select();
        $select->where("id = ?", $id);
        $rows = $this->getTable('Query_Model_DbTable_UserGroupsPaquQQueue')->fetchAll($select);

        return $rows;
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
        if ($row['status'] !== "success") {
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
     * Translates the keys in a fetchRow array into meaningfull and userfriendly
     * strings - format: key = original , value = translation
     * @param array input array with key value pairs resembling output of fetchRow (or tableStats)
     * @return array 
     */
    public function translateColumNames($input) {
        $output = array();

        foreach ($input as $key => $value) {
            if (array_key_exists($key, Query_Model_Resource_PaquQQueue::$translations)) {
                $output[Query_Model_Resource_PaquQQueue::$translations[$key]] = $value;
            } else {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Rename a given table WITHOUT ACL check.
     * @param type $input id OR name of the job
     * @param table name
     * @param new table name
     */
    private function _renameTable($db, $table, $newTable) {
        //due to extremely broken Zend_Db::quoteInto, this looks horrible
        $sql = "RENAME TABLE ";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($table);
        $sql .= " TO ";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($newTable);
        $sql .= ";";

        try {
            $this->getTable()->getAdapter()->query($sql)->closeCursor();
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
    private function _dropTable($db, $table) {
        //due to extremely broken Zend_Db::quoteInto, this looks horrible
        $sql = "DROP TABLE ";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($db);
        $sql .= ".";
        $sql .= $this->getTable()->getAdapter()->quoteIdentifier($table);
        $sql .= ";";

        try {
            $this->getTable()->getAdapter()->query($sql)->closeCursor();
        } catch (Exception $e) {
            //check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }
    }

    private function _changeTableNames($sqloptions) {
        if (array_key_exists('from', $sqloptions)) {
            foreach ($sqloptions['from'] as &$col) {
                if ($col === "database") {
                    unset($col);
                    $sqloptions['from']['database'] = "resultDBName";
                }

                if ($col === "table") {
                    unset($col);
                    $sqloptions['from']['table'] = "resultTableName";
                }
            }
        }

        return $sqloptions;
    }

    private function _replaceJobDBWithHistory($sqloptions) {
        if (array_key_exists('where', $sqloptions)) {
            $sqloptions['where'] = str_replace("qqueue_jobs", "qqueue_history", $sqloptions['where']);
        }

        if (array_key_exists('order', $sqloptions)) {
            $sqloptions['order'] = str_replace("qqueue_jobs", "qqueue_history", $sqloptions['order']);
        }

        return $sqloptions;
    }

    private function _createJobTableUnion($sqloptions) {
        $selectPending = $this->_createJobSelect($sqloptions);
        $selectHistory = $this->_createJobHistorySelect($sqloptions);

        // get the rowset and return
        $select = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->select()
                ->union(array($selectPending, $selectHistory));

        return $select;
    }

    private function _createJobSelect($sqloptions) {
        //build adapter
        $userDBResource = $this->getUserDBResource();
        $config = $userDBResource->getTable()->getAdapter()->getConfig();

        //do some reformulating of the query to adapt to PaQuQQueue
        $sqloptions = $this->_changeTableNames($sqloptions);

        //if there is some sorting going on, remove it and add it later to the union
        if (array_key_exists('order', $sqloptions)) {
            unset($sqloptions['order']);
        }

        // get the primary sql select object
        $selectPending = $this->getTable('Query_Model_DbTable_JobsPaquQQueue')->getSelect($sqloptions);

        $selectPending->where("mysqlUserName = ?", $config['username']);

        // add inner joins for the category and the status
        $selectPending->setIntegrityCheck(false);
        if (in_array('username', $sqloptions['from'])) {
            $selectPending->columns('usrId');
        }

        return $selectPending;
    }

    private function _createJobHistorySelect($sqloptions) {
        //build adapter
        $userDBResource = $this->getUserDBResource();
        $config = $userDBResource->getTable()->getAdapter()->getConfig();

        //do some reformulating of the query to adapt to PaQuQQueue
        $sqloptions = $this->_changeTableNames($sqloptions);

        $sqlHistOptions = $this->_replaceJobDBWithHistory($sqloptions);

        //if there is some sorting going on, remove it and add it later to the union
        if (array_key_exists('order', $sqloptions)) {
            unset($sqloptions['order']);
        }

        $selectHistory = $this->getTable('Query_Model_DbTable_JobsHistoryPaquQQueue')->getSelect($sqlHistOptions);

        $selectHistory->where("mysqlUserName = ?", $config['username']);

        // add inner joins for the category and the status
        $selectHistory->setIntegrityCheck(false);
        if (in_array('username', $sqloptions['from'])) {
            $selectHistory->columns('usrId');
        }

        return $selectHistory;
    }

    private function _resolveUserName(&$rowsArray, $sqloptions) {
        //go through the result set and replace all instances of user_id with string
        if (!empty($rowsArray) && array_key_exists('user_id', $rowsArray[0]) &&
                (in_array('username', $sqloptions['from']) || array_key_exists('username', $sqloptions['from']))) {

            $userArray = array();

            //construct prepared statement
            $userTableName = $this->getTable('Auth_Model_DbTable_User')->getName();
            $adapter = $this->getTable('Auth_Model_DbTable_User')->getAdapter();
            $usrSelect = $adapter->select();
            $usrSelect->from(array($userTableName), array('id', 'username'));
            $usrSelect->where("id = ?");

            $stmt = new Zend_Db_Statement_Pdo($adapter, $usrSelect->__toString());

            foreach ($rowsArray as &$row) {
                //check if we have retrieved this user id and if not, retrieve and cache
                if (!array_key_exists($row['user_id'], $userArray)) {
                    $stmt->execute(array($row['user_id']));
                    $usrRow = $stmt->fetch();
                    if (!empty($usrRow)) {
                        $userArray[$row['user_id']] = $usrRow['username'];
                    } else {
                        $userArray[$row['user_id']] = "unknown";
                    }
                }

                $row['username'] = $userArray[$row['user_id']];

                if (in_array('username', $sqloptions['from']) && !in_array('user_id', $sqloptions['from'])) {
                    unset($row['user_id']);
                }
            }
        }
    }

    private function _resolveStatus(&$rowsArray, $sqloptions) {
        //go through the result set and replace all instances of user_id with string
        if (!empty($rowsArray) && array_key_exists('status', $rowsArray[0]) &&
                in_array('status', $sqloptions['from'])) {

            $statusStrings = array_flip(Query_Model_Resource_PaquQQueue::$status);
            foreach ($rowsArray as &$row) {
                $row['status'] = $statusStrings[$row['status']];
            }
        }
    }

    private function _resolveQueues(&$rowsArray, $sqloptions) {
        //go through the result set and replace all instances of queue with its name
        $queues = $this->fetchQueues();

        if (!empty($rowsArray) && array_key_exists('queue', $rowsArray[0])) {
            foreach ($rowsArray as &$row) {
                $row['queue'] = $queues[$row['queue']]['name'];
            }
        }
    }

    private function _cleanPaquQueries(&$rowsArray, $sqloptions) {
        foreach ($rowsArray as &$row) {
            //if row contains a call to spider_bg_direct_sql, the actual query run on the
            //server will be hidden from the user, since spider_bg_direct_sql needs secret
            //information that nobody should know...

            if (isset($row['actualQuery']) && strpos($row['actualQuery'], "spider_bg_direct_sql") !== false) {
                unset($row['actualQuery']);
            }
        }
    }

    /**
     * Returns the translation array for the columns in show jobs.
     * @return array 
     */
    public function getTranslations() {
        return Query_Model_Resource_PaquQQueue::$translations;
    }

}
