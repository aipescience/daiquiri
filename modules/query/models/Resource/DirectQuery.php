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
     * Array for the columns of the jobs tables.
     * @var array $_cols
     */
    protected static $_cols = array(
        'id' => 'id',
        'table' => 'table',
        'database' => 'database',
        'query' => 'query',
        'actualQuery' => 'actualQuery',
        'user_id' => 'user_id',
        'status_id' => 'Query_Jobs.status_id',
        'time' => 'time'
    );

    /**
     * Field that used best as a timestamp.
     * @var string $_timeField
     */
    protected static $_timeField = 'time';

    /**
     * Creates a new table in the database with the given sql query.
     * SIDE EFFECT: changes $job array and fills in the missing data
     * @param array $job object that hold information about the query
     * @param array $errors holding any error that occurs
     * @param array $options any options that a specific implementation of submitJob needs to get
     * @return int $status
     */
    public function submitJob(&$job, array &$errors, $options = false) {
        // switch to user adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

        // get adapter config
        $config = $this->getAdapter()->getConfig();

        // get tablename
        $table = $job['table'];

        // check if the table already exists
        if ($this->_tableExists($table)) {
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
            return Query_Model_Resource_DirectQuery::$_status['error'];
        }

        // switch to user adapter (it could have been changed by the query, due to a "USE" statement)
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

        // check if it worked
        if (in_array($table, $this->getAdapter()->listTables())) {
            // set status
            $statusId = Query_Model_Resource_DirectQuery::$_status['success'];
        } else {
            $statusId = Query_Model_Resource_DirectQuery::$_status['error'];
        }

        if (!empty($options) && array_key_exists('jobId', $options)) {
            $job['id'] = "{$options['jobId']}";
        }

        $job['database'] = $config['dbname'];
        $job['host'] = $config['host'];
        $job['time'] = date("Y-m-d\TH:i:s");
        $job['user_id'] = Daiquiri_Auth::getInstance()->getCurrentId();
        $job['status_id'] = $statusId;

        // switch to web adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getWebAdapter());

        // insert job into jobs table
        $this->getAdapter()->insert('Query_Jobs', $job);

        // get Id of the new job
        $job['id'] = $this->getAdapter()->lastInsertId();

        // get username and status
        $statusStrings = array_flip(Query_Model_Resource_DirectQuery::$_status);
        $job['status'] = $statusStrings[$statusId];
        $job['username'] = Daiquiri_Auth::getInstance()->getCurrentUsername();

        return $statusId;
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

        // switch to user adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

        // check if the table already exists
        if ($this->_tableExists($newTable)) {
            throw new Exception("Table '{$newTable}' already exists.");
        }

        // rename result table for job
        $this->_renameTable($job['database'], $job['table'], $newTable);

        // switch to web adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getWebAdapter());

        // Updating the job entry
        $this->getAdapter()->update('Query_Jobs', array('table' => $newTable), array('id = ?' => $id));
    }

    /**
     * Delete job with given id. This will also drop the associated
     * @param array $id
     */
    public function removeJob($id) {
        // get job from the database
        $job = $this->fetchRow($id);

        // switch to user adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

        // drop result table for job
        $this->_dropTable($job['database'], $job['table']);

        // switch to web adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getWebAdapter());

        // remove job from job table
        $this->getAdapter()->delete('Query_Jobs', array('id = ?' => $id));
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
        $cols = array();
        foreach (Query_Model_Resource_DirectQuery::$_cols as $col => $dbCol) {
            $cols[$col] = $this->quoteIdentifier('Query_Jobs',$dbCol);
        }
        $cols['username'] = $this->quoteIdentifier('Auth_User','username');
        return $cols;
    }

    /**
     * Counts the number of rows in the jobs table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null) {
        // rewrite sqloptions especially where
        $sqloptions = $this->_processWhere($sqloptions);

        $select = $this->select();
        $select->from('Query_Jobs', 'COUNT(*) as count');
        $select->join('Auth_User','Query_Jobs.user_id = Auth_User.id','username');

        if ($sqloptions) {
            $select->setWhere($sqloptions);
            $select->setOrWhere($sqloptions);
        }

        // query database and return
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }

    /**
     * Fetches a set of rows from the jobs table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        // rewrite sqloptions especially where
        $sqloptions = $this->_processWhere($sqloptions);

        // get the primary sql select object
        $select = $this->select($sqloptions);
        $select->from('Query_Jobs', Query_Model_Resource_DirectQuery::$_cols);
        $select->join('Auth_User','Query_Jobs.user_id = Auth_User.id','username');

        // get the rowset and return
        $rows = $this->fetchAll($select);

        // go through the result set and replace all instances of status with string
        $statusStrings = array_flip(Query_Model_Resource_DirectQuery::$_status);
        foreach ($rows as &$row) {
            $row['status'] = $statusStrings[$row['status_id']];
        }

        return $rows;
    }

    /**
     * Fetches one row specified by its primary key from the jobs table.
     * @param array $sqloptions
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // get the primary sql select object
        $select = $this->select();
        $select->from('Query_Jobs', Query_Model_Resource_DirectQuery::$_cols);
        $select->join('Auth_User','Query_Jobs.user_id = Auth_User.id','username');
        $select->where('Query_Jobs.id = ?', $id);

        // get the rowset and return
        $row = $this->fetchOne($select);
        if (!empty($row)) {
            $statusStrings = array_flip(Query_Model_Resource_DirectQuery::$_status);
            $row['status'] = $statusStrings[$row['status_id']];
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
        // switch to user adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter());

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

    /**
     * Returns rewritten sqloptions array.
     * @param array $sqloptions
     * @return array $sqloptions
     */
    protected function _processWhere($sqloptions) {
        if ($sqloptions) {
            $lexer = new PHPSQLParser\lexer\PHPSQLLexer();

            if (isset($sqloptions['where'])) {
                $where = array();
                foreach($sqloptions['where'] as $key => $value) {
                    if (is_int($key)) {
                        $split = $lexer->split(trim($value));
                    } else {
                        $split = $lexer->split(trim($key));
                    }

                    // replace field
                    $split[0] = str_replace(
                        array_keys(Query_Model_Resource_DirectQuery::$_cols),
                        Query_Model_Resource_DirectQuery::$_cols,
                        $split[0]
                    );

                    if (is_int($key)) {
                        $where[$key] = implode($split);
                    } else {
                        $where[implode($split)] = $value;
                    }
                }
                $sqloptions['where'] = $where;
            }
            if (isset($sqloptions['orWhere'])) {
                $orWhere = array();
                foreach($sqloptions['orWhere'] as $key => $value) {
                    if (is_int($key)) {
                        $split = $lexer->split(trim($value));
                    } else {
                        $split = $lexer->split(trim($key));
                    }

                    // replace field
                    $split[0] = str_replace(
                        array_keys(Query_Model_Resource_DirectQuery::$_cols),
                        Query_Model_Resource_DirectQuery::$_cols,
                        $split[0]
                    );

                    if (is_int($key)) {
                        $orWhere[$key] = implode($split);
                    } else {
                        $orWhere[implode($split)] = $value;
                    }
                }
                $sqloptions['orWhere'] = $orWhere;
            }
        }

        return $sqloptions;
    }

}
