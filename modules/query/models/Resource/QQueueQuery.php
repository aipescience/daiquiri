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

require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/lexer/PHPSQLLexer.php');

class Query_Model_Resource_QQueueQuery extends Query_Model_Resource_AbstractQuery {

    /**
     * Flag if the query interface needs a create table statement.
     * @var bool $needsCreateTable
     */
    public static $needsCreateTable = true;

    /**
     * Flag if the query interface has different queues.
     * @var bool $hasQueues
     */
    public static $hasQueues = true;

    /**
     * Array for the status flags user in the jobs table.
     * @var array $_status
     */
    protected static $_status = array(
        'queued' => 0,
        'running' => 1,
        'removed' => 2,
        'error' => 3,
        'success' => 4,
        'timeout' => 5,
        'killed' => 6
    );

    /**
     * Translateion table to convert the database columns of the job table into something readable.
     * @var array $_translations
     */
    protected static $_translations = array('id' => 'Job id',
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
        'status_id' => 'Internal job status id',
        'error' => 'Error message',
        'tbl_size' => 'Total disk usage (in MB)',
        'tbl_idx_size' => 'Index disk usage (in MB)',
        'tbl_free' => 'Free space in table (in MB)',
        'tbl_row' => 'Approx. row count',
    );

    /**
     * Array for the columns of the jobs tables.
     * @var array $_cols
     */
    protected static $_cols = array(
        'id' => 'id',
        'user_id' => 'usrId',
        'username' => 'usrId',
        'database' => 'resultDBName',
        'table' => 'resultTableName',
        'timeSubmit' => 'timeSubmit',
        'timeExecute' => 'timeExecute',
        'timeFinish' => 'timeFinish',
        'query' => 'query',
        'actualQuery' => 'actualQuery',
        'queue' => 'queue',
        'status' => 'status',
        'error' => 'error'
    );

    /**
     * Field that used best as a timestamp.
     * @var string $_timeField
     */ 
    protected static $_timeField = 'timeSubmit';

    /**
     * Construtor. Sets adapter to the user adapter and the `mysql` database. 
     */
    public function __construct() {
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter('mysql'));
    }

    /**
     * Creates a new table in the database with the given sql query.
     * @param array $job object that hold information about the query
     * @param array $errors holding any error that occurs
     * @param array $options any options that a specific implementation of submitJob needs to get
     * @return int $status
     */
    public function submitJob(&$job, array &$errors, $options = false) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get database name
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $database = Daiquiri_Config::getInstance()->getUserDbName($username);

        // get tablename
        $table = $job['table'];

        // check if the table already exists
        if ($this->_tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        // create the actual sql statement
        $query = $job['query'];
        $planQuery = $job['actualQuery'];
        $actualQuery = $job['fullActualQuery'];
        unset($job['fullActualQuery']);

        // create the job submission query
        if (empty($options) || !array_key_exists('queue', $options)) {
            $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;
        } else {
            // check if queue exists on server, if not use default
            $queues = $this->fetchQueues();

            $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;
            foreach ($queues as $currQueue) {
                if ($currQueue['name'] === $options['queue']) {
                    $queue = $options['queue'];
                }
            }
        }

        if (empty($options) || !array_key_exists('usrGrp', $options)) {
            $userGroup = Daiquiri_Config::getInstance()->query->query->qqueue->defaultUsrGrp;
        } else {
            //check if user group exists on server, if not use default.
            $groups = $this->fetchUserGroups();

            $userGroup = Daiquiri_Config::getInstance()->query->query->qqueue->defaultUsrGrp;
            foreach ($groups as $group) {
                if ($group['name'] === $options['usrGrp']) {
                    $userGroup = $options['usrGrp'];
                }
            }
        }

        $job['id'] = "NULL";
        if (!empty($options) && array_key_exists('jobId', $options)) {
            $job['id'] = "{$options['jobId']}";
        }

        // if the plan query is not the same as the actual query, this means this is run in the context
        // of paqu and we are going to hide the actual query from the user. We therefore add the plan query
        // to the end of the user given query and comment it out, for reference.
        if ($planQuery !== $actualQuery) {
            // split $planQuery into lines
            $query .= "\n-- The query plan used to run this query: --";
            $query .= "\n--------------------------------------------\n--\n";

            foreach ($planQuery as $line) {
                $query .= "-- " . $line . "\n";
            }
        }

        // get user id
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // are we guest?
        if ($userId === null) {
            $userId = 0;
        }

        $sql = "SELECT qqueue_addJob({$job['id']}, {$userId}, '{$userGroup}', '{$queue}', " .
            $this->getAdapter()->quote($actualQuery) .
            ", '{$database}', '{$table}', NULL, 1, " .
            $this->getAdapter()->quote($query) . ");";

        // fire up the database
        try {
            $result = $this->getAdapter()->fetchAll($sql);
        } catch (Exception $e) {
            $errors['submitError'] = $e->getMessage();
            return Query_Model_Resource_QQueueQuery::$_status['error'];
        }

        return $result;
    }

    /**
     * Rename table of a job with given id.
     * @param array $id
     * @throws Exception
     * @param string $newTable new name of the job's table
     */
    public function renameJob($id, $newTable) {
        // get job from the database
        $job = $this->fetchRow($id);

        // check if we really can rename this job
        if ($this->isStatusKillable($job['status'])) {
            throw new Exception("Job is still running.");
        }


        // check if the table already exists
        if ($this->_tableExists($newTable)) {
            throw new Exception("Table '{$newTable}' already exists.");
        }

        $this->_renameTable($job['database'], $job['table'], $newTable);

        // update the job entry
        $this->getAdapter()->update('qqueue_history', array('resultTableName' => $newTable), array('id = ?' => $id));
    }

    /**
     * Delete a job and the corresponding table. CHECK ACLs!!
     * @param array $param
     */
    public function removeJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        // check if we need to kill the query first
        if ($this->isStatusKillable($job['status'])) {
            // just kill the job
            $this->killJob($id);
        } else {
            // drop result table for job
            $this->_dropTable($job['database'], $job['table']);

            // update the job entry
            $this->getAdapter()->update('qqueue_history', array(
                'status' => Query_Model_Resource_QQueueQuery::$_status['removed']
            ), array('id = ?' => $id));
        }
    }

    /**
     * Kill job with given id.
     * @param array $id
     */
    public function killJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        if ($this->isStatusKillable($job['status'])) {
            try {
                $this->getAdapter()->fetchAll("SELECT qqueue_killJob({$job['id']});");
            } catch (Exception $e) {
                // ignore errors here
            }
        }
    }

    /**
     * Returns whether a given status is killable  (true) or not (false).
     * @param string $status
     * @return bool
     */
    public function isStatusKillable($status) {
        if (($status === "queued") || ($status === "running"))
            return true;
        else
            return false;
    }

    /**
     * Returns the columns of the jobs table.
     * @return array $cols
     */
    public function fetchCols() {
        $cols = array();
        foreach (Query_Model_Resource_QQueueQuery::$_cols as $col => $dbCol) {
            $cols[$col] = array(
                $this->quoteIdentifier('qqueue_jobs',$dbCol),
                $this->quoteIdentifier('qqueue_history',$dbCol)
            );
        }
        return $cols;
    }

    /**
     * Counts the number of rows in the jobs table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null, $tableclass = null) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // rewrite sqloptions especially where
        $sqloptions = $this->_processOptions($sqloptions);

        // get the sql select object for the running jobs
        $selectPending = $this->select($sqloptions['pending']);
        $selectPending->from('qqueue_jobs', 'COUNT(*) as count');
        $selectPending->where("qqueue_jobs.mysqlUserName = ?", $config['username']);

        // get the sql select object for the old jobs
        $selectHistory = $this->select($sqloptions['history']);
        $selectHistory->from('qqueue_history', 'COUNT(*) as count');
        $selectHistory->where("qqueue_history.mysqlUserName = ?", $config['username']);

        // get rows
        $rowPending = $this->fetchOne($selectPending);
        $rowHistory = $this->fetchOne($selectHistory);

        // add counts and return
        return ((int) $rowPending['count']) + ((int) $rowHistory['count']);
    }

    /**
     * Fetches a set of rows from the jobs table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // rewrite sqloptions especially where
        $sqloptions = $this->_processOptions($sqloptions);

        // get the sql select object for the running jobs
        $selectPending = $this->select($sqloptions['pending']);
        $selectPending->from('qqueue_jobs', Query_Model_Resource_QQueueQuery::$_cols);
        $selectPending->where("qqueue_jobs.mysqlUserName = ?", $config['username']);

        // get the sql select object for the old jobs
        $selectHistory = $this->select($sqloptions['history']);
        $selectHistory->from('qqueue_history', Query_Model_Resource_QQueueQuery::$_cols);
        $selectHistory->where("qqueue_history.mysqlUserName = ?", $config['username']);
        
        // get the union with the sqloptions
        $select = $this->select($sqloptions)->union(array($selectPending, $selectHistory));

        // fetch rows fromtatbase
        $rows = $this->fetchAll($select);

        // get all usernames, status, queues
        $userResource = new Auth_Model_Resource_User();
        $userCache = array();
        $statusStrings = array_flip(Query_Model_Resource_QQueueQuery::$_status);
        $queues = $this->fetchQueues();
        foreach ($rows as &$row) {
            // check if user is already in cache
            if (!array_key_exists($row['user_id'], $userCache)) {
                $userRow = $userResource->fetchRow($row['user_id']);
                if (empty($userRow)) {
                    $userCache[$row['user_id']] = 'unknown';
                } else {
                    $userCache[$row['user_id']] = $userRow['username'];
                }
            }

            // get username from cache
            $row['username'] = $userCache[$row['user_id']];

            // get status from status string array
            $row['status'] = $statusStrings[$row['status']];

            // get queue
            $row['queue'] = $queues[$row['queue']]['name'];

            // if row contains a call to spider_bg_direct_sql, the actual query run on the
            // server will be hidden from the user, since spider_bg_direct_sql needs secret
            // information that nobody should know...
            if (isset($row['actualQuery']) && strpos($row['actualQuery'], "spider_bg_direct_sql") !== false) {
                unset($row['actualQuery']);
            }
        }

        return $rows;
    }

    /**
     * Fetches one row specified by its primary key from the jobs table.
     * @param array $sqloptions
     * @return array $row
     */
    public function fetchRow($id) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get the sql select object for the running jobs
        $selectPending = $this->select();
        $selectPending->from('qqueue_jobs', Query_Model_Resource_QQueueQuery::$_cols);
        $selectPending->where('qqueue_jobs.mysqlUserName = ?', $config['username']);
        $selectPending->where('qqueue_jobs.id = ?', $id);

        // get the sql select object for the old jobs
        $selectHistory = $this->select();
        $selectHistory->from('qqueue_history', Query_Model_Resource_QQueueQuery::$_cols);
        $selectHistory->where('qqueue_history.mysqlUserName = ?', $config['username']);
        $selectHistory->where('qqueue_history.id = ?', $id);

        $select = $this->select()->union(array($selectPending, $selectHistory));

        // get the rowset and return
        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        }

        // get all usernames, status, queues
        $userResource = new Auth_Model_Resource_User();
        $statusStrings = array_flip(Query_Model_Resource_QQueueQuery::$_status);
        $queues = $this->fetchQueues();

        // get username from cache
        $userRow = $userResource->fetchRow($row['user_id']);
        if (empty($userRow)) {
            $row['username'] = 'unknown';
        } else {
            $row['username'] = $userRow['username'];
        }

        // get status from status string array
        $row['status'] = $statusStrings[$row['status']];

        // get queue
        $row['queue'] = $queues[$row['queue']]['name'];

        // if row contains a call to spider_bg_direct_sql, the actual query run on the
        // server will be hidden from the user, since spider_bg_direct_sql needs secret
        // information that nobody should know...
        if (isset($row['actualQuery']) && strpos($row['actualQuery'], "spider_bg_direct_sql") !== false) {
            unset($row['actualQuery']);
        }
        
        return $row;
    }

    /**
     * Returns the supported queues.
     * @return array $queues 
     */
    public function fetchQueues() {
        $select = $this->select();
        $select->from('qqueue_queues', array("id", "name", "priority", "timeout"));
        return $this->fetchAssoc($select);
    }

    /**
     * Returns a specific supported queue.
     * @param int $id id of the queue
     * @return array $queue
     */
    public function fetchQueue($id) {
        $select = $this->select();
        $select->from('qqueue_queues', array("id", "name", "priority", "timeout"));
        $select->where("id = ?", $id);
        return $this->fetchOne($select);
    }

    /**
     * Returns the default queue.
     * @return array $queue
     */
    public function fetchDefaultQueue() {
        if(empty(Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue)) {
            throw new Exception("PaquQQueue: No default queue defined");
        }

        $select = $this->select();
        $select->from('qqueue_queues', array("id", "name", "priority", "timeout"));
        $select->where("name = ?", Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue);
        return $this->fetchOne($select);
    }

    /**
     * Returns the supported user groups.
     * @return array $userGroups
     */
    public function fetchUserGroups() {
        $select = $this->select();
        $select->from('qqueue_usrGrps', array("id", "name", "priority"));
        return $this->fetchAssoc($select);
    }

    /**
     * Returns a specific supported user group.
     * @param int $id id of the user group
     * @return array $userGroup
     */
    public function fetchUserGroup($id) {
        $select = $this->select();
        $select->from('qqueue_usrGrps', array("id", "name", "priority"));
        $select->where("id = ?", $id);
        return $this->fetchOne($select);
    }

    /**
     * Returns statistical information about the database table corresponding to
     * the job id if exists.
     * @param int $id id of the job
     * @return array $stats
     */
    public function fetchTableStats($id) {
        //first obtain information about this job
        $job = $this->fetchRow($id);

        //only get statistics if the job finished
        if ($job['status'] !== "success") {
            return array();
        }

        if ($this->_isTableLocked($job['table'])) {
            return array();
        } else {
            //check if table is available
            if (!in_array($job['table'], $this->getAdapter()->listTables())) {
                return array();
            }

            // obtain row count
            // obtain table size in MB
            // obtain index size in MB
            // obtain free space (in table) in MB
            $sql = "SELECT round( (data_length + index_length) / 1024 / 1024, 3 ) AS 'tbl_size', " .
                    "round( index_length / 1024 / 1024, 3) AS 'tbl_idx_size', " .
                    "round( data_free / 1024 / 1024, 3 ) AS 'tbl_free', table_rows AS 'tbl_row' " .
                    "FROM information_schema.tables " .
                    "WHERE table_schema = ? AND table_name = ?;";

            return $this->getAdapter()->fetchAll($sql, array($row['database'], $row['table']));
        }
    }

    /**
     * Given a table name, check if it already exists (true) or not (false).
     * @param string $table name of the table
     * @return bool
     */
    protected function _tableExists($table) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get the user db
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $userDb = Daiquiri_Config::getInstance()->getUserDbName($username);

        // first check for pending jobs that might already write to the requested table (bug #209)
        $select = $this->select();
        $select->from('qqueue_jobs', array('id'));
        $select->where('mysqlUserName = ?', $config['username']);
        $select->where('status != 2');
        $select->where('resultTableName = ?', $table);
        $select->where('resultDBName = ?', $userDb);

        $rows = $this->fetchAll($select);

        if (!empty($rows)) {
            return true;
        }

        // then check if table is already there...
        $userTables = Daiquiri_Config::getInstance()->getUserDbAdapter($userDb)->listTables();
        if (in_Array($table,$userTables)) {
            return true;
        }

        return false;
    }

    /**
     * Returns rewritten sqloptions array.
     * @param array $sqloptions
     * @return array $sqloptions
     */
    protected function _processOptions($sqloptions) {
        // prepare sqloptions
        $sqloptions['pending'] = array();
        $sqloptions['history'] = array();

        if ($sqloptions) {
            $lexer = new PHPSQLParser\lexer\PHPSQLLexer();

            $fieldStringPending = $this->quoteIdentifier('qqueue_jobs');
            $fieldStringHistory = $this->quoteIdentifier('qqueue_history');

            if (isset($sqloptions['order'])) {
                $order = array();
                foreach($sqloptions['order'] as $value) {
                    $split = $lexer->split(trim($value));
                    $tmp = explode('.',$split[0]);
                    $order[end($split)] = end($tmp);
                }

                if (count($order) != 1) {
                    throw new Expection('More than one col in '  . get_class($this) . '::' . __METHOD__);
                } else {
                    // revert column again
                    $orderField = array_search(reset($order), Query_Model_Resource_QQueueQuery::$_cols);
                    $sqloptions['order'] = $orderField . ' ' . key($order);
                }
            }

            if (isset($sqloptions['where'])) {
                $sqloptions['pending']['where'] = array();
                $sqloptions['history']['where'] = array();

                foreach($sqloptions['where'] as $key => $value) {
                    if (is_int($key)) {
                        $split = $lexer->split(trim($value));
                    } else {
                        $split = $lexer->split(trim($key));
                    }

                    // replace field
                    $split[0] = str_replace(
                        array_keys(Query_Model_Resource_QQueueQuery::$_cols),
                        Query_Model_Resource_QQueueQuery::$_cols,
                        $split[0]
                    );

                    if (is_int($key)) {
                        if (strpos($split[0],'qqueue_jobs') !== false) {
                            $sqloptions['pending']['where'][] = implode($split);
                        } else if (strpos($split[0],'qqueue_history') !== false) {
                            $sqloptions['history']['where'][] = implode($split);
                        } else {
                            $sqloptions['pending']['where'][] = implode($split);
                            $sqloptions['history']['where'][] = implode($split);
                        }
                    } else {
                        if (strpos($split[0],'qqueue_jobs') !== false) {
                            $sqloptions['pending']['where'][implode($split)] = $value;
                        } else if (strpos($split[0],'qqueue_history') === 0) {
                            $sqloptions['history']['where'][implode($split)] = $value;
                        } else {
                            $sqloptions['pending']['where'][implode($split)] = $value;
                            $sqloptions['history']['where'][implode($split)] = $value;
                        }
                    }
                }

                unset($sqloptions['where']);
            }

            if (isset($sqloptions['orWhere'])) {
                $sqloptions['pending']['orWhere'] = array();
                $sqloptions['history']['orWhere'] = array();

                foreach($sqloptions['orWhere'] as $key => $value) {
                    if (is_int($key)) {
                        $split = $lexer->split(trim($value));
                    } else {
                        $split = $lexer->split(trim($key));
                    }

                    // replace field
                    $split[0] = str_replace(
                        array_keys(Query_Model_Resource_QQueueQuery::$_cols),
                        Query_Model_Resource_QQueueQuery::$_cols,
                        $split[0]
                    );

                    if (is_int($key)) {
                        if (strpos($split[0],$fieldStringPending) === 0) {
                            $sqloptions['pending']['orWhere'][] = implode($split);
                        } else if (strpos($split[0],$fieldStringHistory) === 0) {
                            $sqloptions['history']['orWhere'][] = implode($split);
                        } else {
                            $sqloptions['pending']['orWhere'][] = implode($split);
                            $sqloptions['history']['orWhere'][] = implode($split);
                        }
                    } else {
                        if (strpos($split[0],$fieldStringPending) === 0) {
                            $sqloptions['pending']['orWhere'][implode($split)] = $value;
                        } else if (strpos($split[0],$fieldStringHistory) === 0) {
                            $sqloptions['history']['orWhere'][implode($split)] = $value;
                        } else {
                            $sqloptions['pending']['orWhere'][implode($split)] = $value;
                            $sqloptions['history']['orWhere'][implode($split)] = $value;
                        }
                    }
                }

                unset($sqloptions['orWhere']);
            }
        }

        return $sqloptions;
    }
}
