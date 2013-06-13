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

abstract class Query_Model_Resource_AbstractProcessor extends Daiquiri_Model_Resource_Table {

    //this can be either QPROC_SIMPLE, QPROC_INFOPLAN, QPROC_ALTERPLAN
    public static $planTypes = false;

    /**
     * Processor factory.
     */
    static function factory($queue = 'direct') {

        // get the values from the config
        $processor = Daiquiri_Config::getInstance()->query->processor->name;

        // get the name of the class
        $className = 'Query_Model_Resource_' . ucfirst($processor) . 'Processor';

        if (is_subclass_of($className, 'Query_Model_Resource_AbstractProcessor')) {
            return new $className();
        } else {
            throw new Exception('Unknown query processor: ' . $className);
        }
    }

    /**
     * Constructor. 
     */
    public function __construct() {
        $this->permissions = new Query_Model_Resource_Permissions();
        $this->processing = new Query_Model_Resource_Processing();
    }

    /**
     * Validates a raw query before any processing and altering of the query occurred.
     * 
     * @param string sql query
     * @param string result table name
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return TRUE if valid, FALSE if not
     */
    abstract public function validateQuery($sql, $table, array &$errors, $options = false);

    /**
     * Validates a query plan (if alterable) before submission of the query. If no alteration of the
     * plan is supported by the specific query facility, this function needs to be implemented empty
     * just returning TRUE
     * 
     * @param array plan
     * @param string result table name
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return TRUE if valid, FALSE if not
     */
    abstract public function validatePlan(&$plan, $table, array &$errors, $options = false);

    /**
     * Prepares a job object according to the query plan (if supported), otherwise just prepares a job 
     * according to the processed query (without plan, depending on implementation)
     * 
     * @param array sql query
     * @param array errors holding any error that occurs
     * @param array plan
     * @param string result table name
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return object job
     */
    abstract public function query(&$sql, array &$errors, &$plan = false, $resultTableName = false, $options = false);

    /**
     * Returns the query plan depending on implementation. If an implementation does not support query
     * plans, this needs to return an empty array.
     * 
     * @param array sql query
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return plan 
     */
    abstract public function getPlan(&$sql, array &$errors, $options = false);

    /**
     * Checks whether a given plan execution type is supported by the implementation or not.
     * 
     * @param string plan type
     * @return TRUE if supported, FALSE if not
     */
    public function supportsPlanType($planType) {
        if (in_array($planType, static::$planTypes)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*    /**
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

}
