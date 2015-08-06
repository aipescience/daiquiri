<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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
     * Only these additional keys will be shown to the user.
     * @var array $_translations
     */
    protected static $_translations = array(
        'timeSubmit' => 'Job submitted at',
        'timeExecute' => 'Job started at',
        'timeFinish' => 'Job finished at',
        'queue' => 'Queue'
    );

    /**
     * Array for the fields of qqueue_jobs and qqueue_history tables.
     * @var array $_fields
     */
    protected static $_fields = array(
        'id' => 'id',
        'user_id' => 'usrId',
        'database' => 'resultDBName',
        'table' => 'resultTableName',
        'timeSubmit' => 'timeSubmit',
        'timeExecute' => 'timeExecute',
        'timeFinish' => 'timeFinish',
        'query' => 'query',
        'actualQuery' => 'actualQuery',
        'queue' => 'queue',
        'status_id' => 'status',
        'error' => 'error'
    );

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
        // check if the table already exists
        if ($this->_tableExists($job['table'])) {
            $errors['submitError'] = "Table '{$job['table']}' already exists";
            return false;
        }

        // get jobId from the options, or calculate
        if (!empty($options) && isset($options['jobId'])) {
            $job['id'] = "{$options['jobId']}";
        } else {
            $job['id'] = $this->_calculateJobId();
        }

        // get the queue from the options
        if (!empty($options) && isset($options['queue']) && in_array($options['queue'],$this->_queues())) {
            $queue = $options['queue'];
        } else {
            $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;
        }

        // get the group of the user from the options
        if (!empty($options) && isset($options['usrGrp']) && in_array($options['usrGrp'],$this->_usrGrps())) {
            $usrGrp = $options['usrGrp'];
        } else {
            $usrGrp = Daiquiri_Config::getInstance()->query->query->qqueue->defaultUsrGrp;
        }

        // create the actual sql statement
        $query = $job['query'];
        $planQuery = $job['actualQuery'];
        $actualQuery = $job['fullActualQuery'];
        unset($job['fullActualQuery']);

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

        // create sql statement
        $quotedQuery = $this->getAdapter()->quote($query);
        $quotedActualQuery = $this->getAdapter()->quote($actualQuery);
        $sql = "SELECT qqueue_addJob({$job['id']}, {$job['user_id']}, '{$usrGrp}', '{$queue}', {$quotedActualQuery}, '{$job['database']}', '{$job['table']}', NULL, 1, {$quotedQuery});";

        // fire up the database
        try {
            $result = $this->getAdapter()->fetchAll($sql);
        } catch (Exception $e) {
            $errors['submitError'] = $e->getMessage();
            return Query_Model_Resource_QQueueQuery::$_status['error'];
        }

        // fetch the new jobs row for the timestamp and the status
        $row = $this->fetchRow($job['id']);
        $job['time'] = $row['timeSubmit'];
        $job['status_id'] = $row['status_id'];

        // insert job into jobs table
        $this->getJobResource()->insertRow($job);
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
        $this->getAdapter()->update('qqueue_history', array(
            'resultTableName' => $newTable
        ), array('id = ?' => $id));
        $this->getJobResource()->updateRow($id, array('table' => $newTable));
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

            // update the job in the Query_Jobs table
            $this->_updateJob($job);
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

            // update the job in the Query_Jobs table
            $this->_updateJob($job);
        }
    }

    /**
     * Returns the columns of the jobs table.
     * @return array $cols
     */
    public function fetchCols() {
        return $this->getJobResource()->fetchCols();
    }

    /**
     * Counts the number of rows in the jobs table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null, $tableclass = null) {
        return $this->getJobResource()->countRows($sqloptions);
    }

    /**
     * Fetches a set of rows from Query_Jobs specified by $sqloptions.
     * Checks the current status for non completed jobs and updates accordingly.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $rows = $this->getJobResource()->fetchRows($sqloptions);

        foreach ($rows as &$row) {
            if ($row['complete'] !== '1') {
                $this->_updateJob($row);
            }

            $row['status'] = $this->getStatus($row['status_id']);
            $row['type'] = $this->getJobResource()->getType($row['type_id']);
        }

        return $rows;
    }

    /**
     * Fetches one job from the qqueue_jobs and qqueue_history tables.
     * @param array $sqloptions
     * @return array $row
     */
    public function fetchRow($id) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get the sql select object for the running jobs
        $selectPending = $this->select();
        $selectPending->from('qqueue_jobs', Query_Model_Resource_QQueueQuery::$_fields);
        $selectPending->where('qqueue_jobs.mysqlUserName = ?', $config['username']);
        $selectPending->where('qqueue_jobs.id = ?', $id);

        // get the sql select object for the old jobs
        $selectHistory = $this->select();
        $selectHistory->from('qqueue_history', Query_Model_Resource_QQueueQuery::$_fields);
        $selectHistory->where('qqueue_history.mysqlUserName = ?', $config['username']);
        $selectHistory->where('qqueue_history.id = ?', $id);

        $select = $this->select()->union(array($selectPending, $selectHistory));

        // get the rowset
        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        }

        // get the values from the Query_Jobs table
        $jobRow = $this->getJobResource()->fetchRow($id);
        foreach (array('time','prev_status_id','type_id','nrows','size','complete') as $key) {
            $row[$key] = $jobRow[$key];
        }

        // get queue
        $queues = $this->_queues();
        $row['queue'] = $queues[$row['queue']];

        // get status from status string array
        $row['status'] = $this->getStatus($row['status_id']);
        if (isset($row['prev_status_id'])) {
            $row['prev_status'] = $this->getStatus($row['prev_status_id']);
        }

        // get type
        $row['type'] = $this->getJobResource()->getType($row['type_id']);

        // calculate queue and query times
        if ($row['timeSubmit'] != '0000-00-00 00:00:00' && $row['timeExecute'] != '0000-00-00 00:00:00') {
            $row['timeQueue'] = strtotime($row['timeExecute']) - strtotime($row['timeSubmit']);
        }
        if ($row['timeExecute'] != '0000-00-00 00:00:00' && $row['timeFinish'] != '0000-00-00 00:00:00') {
            $row['timeQuery'] = strtotime($row['timeFinish']) - strtotime($row['timeExecute']);
        }

        // if row contains a call to spider_bg_direct_sql, the actual query run on the
        // server will be hidden from the user, since spider_bg_direct_sql needs secret
        // information that nobody should know...
        if (isset($row['actualQuery']) && strpos($row['actualQuery'], "spider_bg_direct_sql") !== false) {
            unset($row['actualQuery']);
        }

        return $row;
    }

    /**
     * Returns the number of rows and the size of a given user database.
     * @param int $userId id of the user
     * @return array $stats
     */
    public function fetchStats($userId) {
        return $this->getJobResource()->fetchStats($userId);
    }

    /**
     * Fetches information about the queues.
     * @return array $queues
     */
    public function fetchConfig() {
        return Daiquiri_Config::getInstance()->query->query->qqueue->toArray();
    }

    /**
     * Fetches the number of active jobs in the queue.
     * @return int $nactive
     */
    public function fetchNActive() {
        // get number of running jobs for all applications
        $select = $this->select();
        $select->from('qqueue_jobs', 'COUNT(*) as count');

        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }

    /**
     * Returns true if given status is killable.
     * @param string $status
     * @return bool $killable
     */
    public function isStatusKillable($status) {
        if (($status === "queued") || ($status === "running")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the supported from the qqueue tables queues.
     * @return array $queues
     */
    public function _queues() {
        $select = $this->select();
        $select->from('qqueue_queues', array("id", "name"));
        return $this->fetchPairs($select);
    }

    /**
     * Returns the supported user groups from the qqueue tables .
     * @return array $userGroups
     */
    public function _usrGrps() {
        $select = $this->select();
        $select->from('qqueue_usrGrps', array("id", "name"));
        return $this->fetchPairs($select);
    }

    /**
     * Returns the number of rows and the size of the table (in bytes).
     * @param string $database name of the database
     * @param string $table name of the table
     * @return array $stats
     */
    protected function _tableStats($database,$table) {

        if ($this->_isTableLocked($table)) {
            return array();
        } else {
            // get the user db
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $userDb = Daiquiri_Config::getInstance()->getUserDbName($username);

            $userDbAdapter = Daiquiri_Config::getInstance()->getUserDbAdapter($userDb);
            $userTables = $userDbAdapter->listTables();

            // check if table is available
            if (!in_array($table, $userTables)) {
                return array();
            }

            $sql = 'SELECT table_rows as nrows, data_length + index_length AS size FROM information_schema.tables WHERE table_schema = ? AND table_name = ?;';

            $rows = $this->getAdapter()->fetchAll($sql, array($database,$table));
            return $rows[0];
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
     * Calculate unique (hopefully) job id in the simular but not identical way qqueue does it
     * @return int $jobId
     */
    private function _calculateJobId() {
        $now = gettimeofday();
        $jobId = $now['sec'] * 1000000000 + $now['usec'] * 1000 + mt_rand(0,999);
        return $jobId;
    }

    /**
     * Update job
     * @param array $job object that hold information about the query
     */
    private function _updateJob(&$job) {
        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get the sql select object for the running jobs
        $selectPending = $this->select();
        $selectPending->from('qqueue_jobs');
        $selectPending->where('qqueue_jobs.mysqlUserName = ?', $config['username']);
        $selectPending->where('qqueue_jobs.id = ?', $job['id']);

        // get the sql select object for the old jobs
        $selectHistory = $this->select();
        $selectHistory->from('qqueue_history');
        $selectHistory->where('qqueue_history.mysqlUserName = ?', $config['username']);
        $selectHistory->where('qqueue_history.id = ?', $job['id']);

        $select = $this->select()->union(array($selectPending, $selectHistory));

        // get the rowset and return
        $row = $this->fetchOne($select);
        if (empty($row)) {
            throw new Exception('Job not found');
        }

        // check if the status has changed
        if ($job['status_id'] != $row['status']) {
            $job['prev_status_id'] = $job['status_id'];
            $job['status_id'] = $row['status'];
            $this->getJobResource()->updateRow($job['id'], array(
                'status_id' => $job['status_id'],
                'prev_status_id' => $job['prev_status_id']
            ));
        }

        $status = $this->getStatus($job['status_id']);
        if ($status === 'success') {
            // try to get the table stats
            $stats = $this->_tableStats($job['database'],$job['table']);

            if (!empty($stats)) {
                $job['nrows'] = $stats['nrows'];
                $job['size'] = $stats['size'];

                // set the values and the complete flag
                $this->getJobResource()->updateRow($job['id'], array(
                    'nrows' => $job['nrows'],
                    'size' => $job['size'],
                    'complete' => true
                ));
            }
        } else if (in_array($status, array('removed','error','timeout','killed'))) {
            $this->getJobResource()->removeRow($id, $this->getStatusId('removed'));
        }
    }
}
