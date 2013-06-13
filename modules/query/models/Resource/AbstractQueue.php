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

abstract class Query_Model_Resource_AbstractQueue extends Daiquiri_Model_Resource_Table {

    public static $needsCreateTable = false;
    public static $hasQueues = false;

    /**
     * Queue factory.
     */
    static function factory() {

        // get the values from the config
        $queue = Daiquiri_Config::getInstance()->query->queue->type;

        // get the name of the class
        if ($queue == 'simple') {
            $className = 'Query_Model_Resource_SimpleQueue';
        } else if ($queue == 'qqueue') {
            $className = 'Query_Model_Resource_PaquQQueue';
        } else {
            throw new Exception('Unknown query queue: ' . $className);
        }

        if (is_subclass_of($className, 'Query_Model_Resource_AbstractQueue')) {
            return new $className();
        } else {
            throw new Exception('Unknown query queue: ' . $className);
        }
    }

    /**
     * Constructor. Sets the database adapter.
     */
    public function __construct() {
        
    }

    /**
     * Creates a new table in the database with the given sql query.
     * 
     * @param array job object that hold information about the query
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of submitJob needs to get
     * @return int status id
     */
    abstract public function submitJob(&$job, array &$errors, $options = false);

    /**
     * Create a new job. Adds it to the database.
     * @param array $param
     */
    abstract public function createJob(array $params);

    /**
     * Delete job with given id. This will also drop the associated
     * 
     * @param array $id
     * @return status
     */
    abstract public function removeJob($id);

    /**
     * Kill job with given id.
     * @param array $id
     * @return status
     */
    abstract public function killJob($id);

    /**
     * Rename table of a job with given id.
     * @param array $id
     * @param new table name
     * @return status
     */
    abstract public function renameJob($id, $newTable);

    /**
     * Given a table name, check if it already exists in the user database
     * @param database name
     * @param table name
     * @return true or false
     */
    abstract public function tableExists($table);

    /**
     * Get job status of job with given id. Job status is given as 
     * string (not the numerical value stored in the DB).
     * @param array $param
     * @return status string
     */
    //abstract public function getJobStatus($id);

    /**
     * Returns an array with a configured DB query request . This function must 
     * ensure that a row with a job is returned as such: 0: id, 1: name/table name, 2: status
     * @param int $userId
     * @return array
     */
    abstract public function getSqloptionsForIndex($userId);

    /**
     * Returns true if given status is killable and false, if job cannot be killed
     * @param string $status
     * @return bool
     */
    abstract public function isStatusKillable($status);

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param array $sqloptions
     * @return array 
     */
    public function fetchRows($sqloptions = array()) {
        throw new Exception("fetchRows not implemented");
    }

    /**
     * Returns a set of rows from the (joined) tables specified by $sqloptions.
     * @param int id
     * @return array 
     */
    public function fetchRow($id) {
        throw new Exception("fetchRow not implemented");
    }

    /**
     * Returns the columns of the (joined) tables.
     * @return array 
     */
    public function fetchCols() {
        throw new Exception("fetchCols not implemented");
    }

    /**
     * Returns statistical information about the database table corresponding to
     * the job id if exists.
     * @param int id
     * @return array 
     */
    abstract public function fetchTableStats($id);

    /**
     * Executes a query plainly. No metadata added, just the result set
     * is returned. This uses Zend fetchAll and return set form can be
     * defined through $this->getTable()->getAdapter()->setFetchMode(). 
     * See Zend documentation for further information.
     * @param array that holds the result table and the query
     * @return result set
     */
    public function plainQuery($query) {
        return $this->getTable()->getAdapter()->fetchAll($query);
    }

    /**
     * Returns an unset DB Table resource which is hooked up to the User DB Adapter
     * @return DBAdapter
     */
    protected function getUserDBResource() {
        //NOTE: Cannot be done differently! Adapter needs to be set to a DB, otherwise
        //      database cannot work. Database cannot have db set to ""...
        //build adapter
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $userDBName = Daiquiri_Config::getInstance()->getUserDbName($username);
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);

        $userDBResource = new Daiquiri_Model_Resource_Table();

        $userDBResource->setTable('Daiquiri_Model_DbTable_Simple', $userDBName);
        $userDBResource->getTable()->setAdapter($adapter);

        return $userDBResource;
    }

    /**
     * Returns statistical information about the complete database
     * @return array 
     */
    public function fetchDatabaseStats() {
        // get adapter details
        $config = $this->getUserDBResource()->getTable()->getAdapter()->getConfig();
        $database = $config['dbname'];

        //check if this table is locked and if yes, don't query information_schema. This will result in a
        //"waiting for metadata lock" and makes daiquiri hang
        $adapter = $this->getUserDBResource()->getTable()->getAdapter();
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $database . '` WHERE In_use > 0')->fetchAll();

        $where = "";
        foreach ($lockedTables as $table) {
            $where .= " AND table_name != '" . $table['Table'] . "'";
        }

        //obtain row count
        //obtain table size in MB
        //obtain index size in MB
        //obtain free space (in table) in MB
        $sql = "SELECT round( sum(data_length + index_length) / 1024 / 1024, 3 ) AS 'db_size', " .
                "round( sum(index_length) / 1024 / 1024, 3) AS 'db_idx_size', " .
                "round( sum(data_free) / 1024 / 1024, 3 ) AS 'db_free', sum(table_rows) AS 'db_row' " .
                "FROM information_schema.tables " .
                "WHERE table_schema = ?" . $where;

        $stmt = $this->getUserDBResource()->getTable()->getAdapter()->query($sql, array($database));

        $row = $stmt->fetch();

        return $row;
    }

    /**
     * Returns the translation array for the columns in show jobs.
     * @return array 
     */
    abstract public function getTranslations();
}
