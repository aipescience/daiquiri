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

class Query_Model_Resource_DirectQuery extends Query_Model_Resource_AbstractQuery {

    /**
     * Flag if the query interface needs a create table statement.
     * @var bool $needsCreateTable
     */
    public static $needsCreateTable = true;

    /**
     * Flag if the query interface has different queues.
     * @var bool $hasQueues
     */
    public static $hasQueues = false;

    /**
     * Array for the status flags user in the jobs table.
     * @var array $status
     */
    protected static $_status = array('success' => 1, 'error' => 2, 'removed' => 3);

    /**
     * Translation table to convert the database columns of the job table into something readable.
     * Only these additional keys will be shown to the user. Empty for the direct query.
     * @var array $translations
     */
    protected static $_translations = array();

    /**
     * Construtor. Sets adapter to the user adapter and the users database.
     */
    public function __construct() {
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());
    }

    /**
     * Creates a new table in the database with the given sql query.
     * SIDE EFFECT: changes $job array and fills in the missing data
     * @param array $job object that holds information about the query
     * @param array $errors holding any error that occurs
     * @param array $options any options that a specific implementation of submitJob needs to get
     * @return int $status
     */
    public function submitJob(&$job, array &$errors, $options = false) {
        // check if the table already exists
        if ($this->_tableExists($job['table'])) {
            $errors['submitError'] = "Table '{$job['table']}' already exists";
            return;
        }

        // get jobId from the options, or not
        if (!empty($options) && array_key_exists('jobId', $options)) {
            $job['id'] = "{$options['jobId']}"; // will be overwritten later on by "true" jobId => remove?
        }

        // create the actual sql statement
        $actualQuery = $job['fullActualQuery'];
        unset($job['fullActualQuery']);

        // fire up the database
        // determining the DB adapter that is used. if we have thought about that one, use direct querying
        // without using prepared statement (not that fast and uses memory)
        // if not, fall back to prepared statements querying (using adapter->query abstractions of ZEND)

        $adaptType = get_class($this->getAdapter());

        // if query syntax is checked server side without executing query (like using paqu_validateSQL in MySQL),
        // we just fire up the query. if not, we need to split multiline queries up and check for any exception
        // raised by the server
        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            if (strpos(strtolower($adaptType), "pdo") !== false) {
                try {
                    $stmt = $this->getAdapter()->getConnection()->exec($actualQuery);
                } catch (Exception $e) {
                    $errors['submitError'] = $e->getMessage();
                }
            } else {
                // fallback version
                try {
                    $stmt = $this->getAdapter()->query($actualQuery);
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
                        $stmt = $this->getAdapter()->getConnection()->exec($query);
                    } catch (Exception $e) {
                        $errors['submitError'] = $e->getMessage();
                        break;
                    }
                } else {
                    try {
                        $stmt = $this->getAdapter()->query($query);
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
            $job['status_id'] = Query_Model_Resource_DirectQuery::$_status['error'];
            return;
        }

        // switch to user adapter (it could have been changed by the query, due to a "USE" statement)
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

        // get stats of the new table
        $stats = $this->_tableStats($job['database'],$job['table']);

        // set status
        if (empty($stats)) {
            $job['status_id'] = Query_Model_Resource_DirectQuery::$_status['error'];
        } else {
            $job['status_id'] = Query_Model_Resource_DirectQuery::$_status['success'];
            $job['nrows'] = $stats['nrows'];
            $job['size'] = $stats['size'];
        }

        // set other fields in job object

        // if no time is set yet (or it is Null/0's), then create it now, otherwise keep the stored (creation) time
        if (!empty($options) && array_key_exists('creationTime', $options)) {
            $job['time'] = $options['creationTime'];
        }
        // check if time is 0, even if it was already set (because if creationTime was NULL, we do not want to use this)
        if ($job['time'] == false || $job['time'] == "0000-00-00 00:00:00" || $job['time'] == NULL) {
            $job['time'] = date("Y-m-d H:i:s");
        }

        $job['complete'] = true;

        // insert job into jobs table // is this the one on the server??
        $job['id'] = $this->getJobResource()->insertRow($job);
    }

    /**
     * Rename table of a job with given id.
     * @param array $id
     * @throws Exception
     * @param string $newTable new name of the job's table
     */
    public function renameJob($id, $newTable) {
        // get job from the database
        $job = $this->getJobResource()->fetchRow($id);

        // check if the table already exists
        if ($this->_tableExists($newTable)) {
            throw new Exception("Table '{$newTable}' already exists.");
        }

        // rename result table for job
        $this->_renameTable($job['database'], $job['table'], $newTable);

        // Update the job entry in the user table
        $this->getJobResource()->updateRow($id, array('table' => $newTable));
    }

    /**
     * Delete job with given id. This will also drop the associated
     * @param array $id
     */
    public function removeJob($id) {
        // get job from the database
        $job = $this->getJobResource()->fetchRow($id);

        // drop result table for job
        $this->_dropTable($job['database'], $job['table']);

        // remove job from job table
        $this->getJobResource()->removeRow($id, $this->getStatusId('removed'));
    }

    /**
     * Kill job with given id.
     * @param array $id
     */
    public function killJob($id) {
        // kill is not supported by this queue... thus do nothing
        // we should set here the job phase to ABORTED (e.g. on timeout)
        // and (if not existing) the endTime to the current time
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
    public function countRows(array $sqloptions = null) {
        return $this->getJobResource()->countRows($sqloptions);
    }

    /**
     * Fetches a set of rows from the jobs table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $rows = $this->getJobResource()->fetchRows($sqloptions);

        foreach ($rows as &$row) {
            $row['status'] = $this->getStatus($row['status_id']);
            $row['type'] = $this->getJobResource()->getType($row['type_id']);
        }

        return $rows;
    }

    /**
     * Fetches one row specified by its primary key from the jobs table.
     * @param array $sqloptions
     * @return array $row
     */
    public function fetchRow($id) {
        $row = $this->getJobResource()->fetchRow($id);
        if (!empty($row)) {
            $row['status'] = $this->getStatus($row['status_id']);
            $row['prev_status'] = $this->getStatus($row['prev_status_id']);
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
     * Returns false since no queues are available.
     * @return mixed $queues
     */
    public function fetchConfig() {
        return false;
    }

    /**
     * Returns false since no queues are available.
     * @return mixed $nactive
     */
    public function fetchNActive() {
        return false;
    }

    /**
     * Returns true if given status is killable.
     * @param string $status
     * @return bool $killable
     */
    public function isStatusKillable($status) {
        return false;
    }

    /**
     * Returns the number of rows and the size of the table (in bytes).
     * @param string $database name of the database
     * @param string $table name of the table
     * @return array $stats
     */
    protected function _tableStats($database,$table) {
        // check if table is available
        if (!in_array($table, $this->getAdapter()->listTables())) {
            return array();
        }

        $sql = $sql = 'SELECT table_rows as nrows, data_length + index_length AS size FROM information_schema.tables WHERE table_schema = ? AND table_name = ?;';

        $rows = $this->getAdapter()->fetchAll($sql, array($database, $table));
        return $rows[0];
    }

    /**
     * Given a table name, check if it already exists (true) or not (false).
     * @param string $table name of the table
     * @return bool
     */
    protected function _tableExists($table) {
        $userTables = $this->getAdapter()->listTables();
        if (in_Array($table,$userTables)) {
            return true;
        }
        return false;
    }
}
