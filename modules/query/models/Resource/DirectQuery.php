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
    protected static $_status = array('success' => 1, 'error' => 2);

    /**
     * Translateion table to convert the database columns of the job table into something readable.
     * @var array $translations
     */
    protected static $_translations = array(
        'id' => 'Job id',
        'user_id' => 'Internal user id',
        'username' => 'User name',
        'database' => 'Database name',
        'table' => 'Table name',
        'time' => 'Job submitted at',
        'query' => 'Original query',
        'actualQuery' => 'Actual query',
        'status_id' => 'Internal job status id',
        'status' => 'Job status',
        'tbl_size' => 'Total disk usage [MB]',
        'tbl_idx_size' => 'Index disk usage [MB]',
        'tbl_free' => 'Free space in table [MB]',
        'tbl_row' => 'Approx. row count',
    );

    /**
     * Construtor. Sets adapter to the user adapter and the users database.
     */
    public function __construct() {
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());
    }

    /**
     * Creates a new table in the database with the given sql query.
     * SIDE EFFECT: changes $job array and fills in the missing data
     * @param array $job object that hold information about the query
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
            $job['id'] = "{$options['jobId']}";
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

        // check if it worked
        if (in_array($job['table'], $this->getAdapter()->listTables())) {
            // set status
            $job['status_id'] = Query_Model_Resource_DirectQuery::$_status['success'];
        } else {
            $job['status_id'] = Query_Model_Resource_DirectQuery::$_status['error'];
        }

        // set timestamp in job object
        $job['time'] = date("Y-m-d\TH:i:s");

        // insert job into jobs table
        $this->getJobResource()->insertRow($job);

        // get job id from database
        $job['id'] = $this->getJobResource()->getAdapter()->lastInsertId();
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
        $this->getJobResource()->deleteRow($id);
    }

    /**
     * Kill job with given id.
     * @param array $id
     */
    public function killJob($id) {
        // kill is not supported by this queue... thus do nothing
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
        }
        return $row;
    }

    /**
     * Returns statistical information about the database table if exists.
     * @param string $database name of the database
     * @param string $table name of the table
     * @return array $stats
     */
    public function fetchTableStats($database,$table) {
        // check if this table is locked and if yes, don't query information_schema. This will result in a
        // "waiting for metadata lock" and makes daiquiri hang
        if ($this->_isTableLocked($table)) {
            return array();
        } else {
            // check if table is available
            if (!in_array($table, $this->getAdapter()->listTables())) {
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

            $rows = $this->getAdapter()->fetchAll($sql, array($database, $table));
            return $rows[0];
        }
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
