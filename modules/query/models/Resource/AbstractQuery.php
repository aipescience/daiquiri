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

abstract class Query_Model_Resource_AbstractQuery extends Daiquiri_Model_Resource_Adapter {

    /**
     * Flag if the query interface needs a create table statement.
     * @var bool $needsCreateTable
     */
    public static $needsCreateTable = false;

    /**
     * Flag if the query interface has different queues.
     * @var bool $hasQueues
     */
    public static $hasQueues = false;

    /**
     * Queue factory.
     * @return Query_Model_Resource_AbstractQuery $queue
     */
    static function factory() {
        // get the values from the config
        $queue = Daiquiri_Config::getInstance()->query->query->type;

        // get the name of the class
        if ($queue == 'direct') {
            $className = 'Query_Model_Resource_DirectQuery';
        } else if ($queue == 'qqueue') {
            $className = 'Query_Model_Resource_QQueueQuery';
        } else {
            throw new Exception('Unknown query type: ' . $queue);
        }

        if (is_subclass_of($className, 'Query_Model_Resource_AbstractQuery')) {
            return new $className();
        } else {
            throw new Exception('Unknown jobs type: ' . $className);
        }
    }

    /**
     * Creates a new table in the database with the given sql query.
     * @param array $job object that hold information about the query
     * @param array $errors holding any error that occurs
     * @param array $options any options that a specific implementation of submitJob needs to get
     */
    abstract public function submitJob(&$job, array &$errors, $options = false);

    /**
     * Rename table of a job with given id.
     * @param array $id
     * @param string $newTable new name of the job's table
     */
    abstract public function renameJob($id, $newTable);

    /**
     * Delete job with given id. This will also drop the associated
     * @param array $id
     */
    abstract public function removeJob($id);

    /**
     * Kill job with given id.
     * @param array $id
     */
    abstract public function killJob($id);

    /**
     * Returns true if given status is killable and false, if job cannot be killed
     * @param string $status
     * @return bool
     */
    abstract public function isStatusKillable($status);

    /**
     * Returns the columns of the (joined) tables.
     * @return array $cols
     */
    abstract public function fetchCols();

    /**
     * Counts the number of rows in the jobs table.
     * Takes where conditions into account.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    abstract public function countRows(array $sqloptions = null);

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array $rows
     */
    abstract public function fetchRows(array $sqloptions = array());

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param int $id
     * @return array $rows
     */
    abstract public function fetchRow($id);

    /**
     * Returns statistical information about the database table corresponding to
     * the job id if exists.
     * @param int $id id of the job
     * @return array $stats
     */
    abstract public function fetchTableStats($id);

    /**
     * Returns statistical information about the complete database
     * @return array $stats
     */
    public function fetchDatabaseStats() {
        // check if this table is locked and if yes, don't query information_schema. This will result in a
        // "waiting for metadata lock" and makes daiquiri hang
        $adapter = $this->getAdapter();
        $config = $this->getAdapter()->getConfig();

        // get list of locked tables
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $config['dbname'] . '` WHERE In_use > 0')->fetchAll();
        $where = "";
        foreach ($lockedTables as $table) {
            $where .= " AND table_name != '" . $table['Table'] . "'";
        }

        // obtain row count
        // obtain table size in MB
        // obtain index size in MB
        // obtain free space (in table) in MB
        $sql = "SELECT round( sum(data_length + index_length) / 1024 / 1024, 3 ) AS 'db_size', " .
                "round( sum(index_length) / 1024 / 1024, 3) AS 'db_idx_size', " .
                "round( sum(data_free) / 1024 / 1024, 3 ) AS 'db_free', sum(table_rows) AS 'db_row' " .
                "FROM information_schema.tables " .
                "WHERE table_schema = ?" . $where;

        return $adapter->query($sql, array($config['dbname']))->fetch();
    }

    /**
     * Returns the translation array for the columns in show jobs.
     * @return array $translations
     */
    public function getTranslations() {
        $classname = get_class($this);
        return $classname::$_translations;
    }

    /**
     * Rename a given table WITHOUT ACL check.
     * @param string $db name of the database
     * @param string $table current name of the table
     * @param string $newTable new name of the table
     */
    protected function _renameTable($db, $table, $newTable) {
        $sql = "RENAME TABLE ";
        $sql .= $this->quoteIdentifier($db,$table);
        $sql .= " TO ";
        $sql .= $this->quoteIdentifier($db,$newTable);
        $sql .= ";";
        
        try {
            $this->getAdapter()->query($sql)->fetch();
        } catch (Exception $e) {
            // check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }
    }

    /**
     * Drop a given table WITHOUT ACL check.
     * @param string $db name of the database
     * @param string $table name of the table
     */
    protected function _dropTable($db, $table) {
        try {
            $sql = 'DROP TABLE ' . $this->quoteIdentifier($db,$table) . ';';
            $this->getAdapter()->query($sql)->fetch();
        } catch (Exception $e) {
            // check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }
    }

    /**
     * Checks if a job table is locked.
     * @param string $table name of the table
     * @return bool $locked
     */
    protected function _isTableLocked($table) {
        // get config
        $config = $this->getAdapter()->getConfig();

        $lockedTables = $this->getAdapter()->fetchAll('SHOW OPEN TABLES IN `' . $config['dbname'] . '` WHERE In_use > 0');
        $locked = false;
        foreach ($lockedTables as $table) {
            if ($table['Table'] === $job['table']) {
                $locked = true;
                break;
            }
        }

        return $locked;
    }
}
