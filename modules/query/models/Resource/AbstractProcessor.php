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

abstract class Query_Model_Resource_AbstractProcessor extends Daiquiri_Model_Resource_Abstract {

    /**
     * Plan type. This can be either QPROC_SIMPLE, QPROC_INFOPLAN, QPROC_ALTERPLAN
     * @var string $planTypes
     */
    public static $planTypes = false;

    /**
     * Processor factory.
     * @return Query_Model_Resource_AbstractProcessor $processor
     */
    public static function factory() {
        // get the values from the config
        $processor = Daiquiri_Config::getInstance()->query->processor->type;

        // get the name of the class
        $className = 'Query_Model_Resource_' . ucfirst($processor) . 'Processor';

        if (is_subclass_of($className, 'Query_Model_Resource_AbstractProcessor')) {
            return new $className();
        } else {
            throw new Exception('Unknown query processor: ' . $className);
        }
    }

    /**
     * Permissions resource.
     * @var Query_Model_Resource_Permissions $_permissions
     */
    protected $_permissions;

    /**
     * Processing resource.
     * @var Query_Model_Resource_Processing $_processing
     */
    protected $_processing;

    /**
     * Name of the database the results of the query are stores in.
     * @var string $_resultDb
     */
    protected $_userDb;

    /**
     * Constructor. Sets processing and permissions resource.
     */
    public function __construct() {
        $this->_permissions = new Query_Model_Resource_Permissions();
        $this->_processing = new Query_Model_Resource_Processing();

        // get current user
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        if ($username === null) {
            $username = 'Guest';
        }

        $this->_userDb = Daiquiri_Config::getInstance()->getUserDbName($username);
    }

    /**
     * Validates a raw query before any processing and altering of the query occurred.
     * @param string $sql query string
     * @param string $table name of the job's table
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return bool $success
     */
    abstract public function validateQuery($sql, $table, array &$errors, $options = false);

    /**
     * Validates a query plan (if alterable) before submission of the query. If no alteration of the
     * plan is supported by the specific query facility, this function needs to be implemented empty
     * just returning true
     * @param array $plan $query plan
     * @param string $table name of the job's table
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return bool $success
     */
    abstract public function validatePlan(&$plan, $table, array &$errors, $options = false);

    /**
     * Prepares a job object according to the query plan (if supported), otherwise just prepares a job 
     * according to the processed query (without plan, depending on implementation)
     * @param string $sql query string
     * @param array $errors array holding any errors that occur
     * @param array $plan $query plan
     * @param string $table name of the job's table
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return array $job
     */
    abstract public function query(&$sql, array &$errors, &$plan = false, $resultTableName = false, $options = false);

    /**
     * Returns the query plan depending on implementation. If an implementation does not support query
     * plans, this needs to return an empty array.
     * @param array $plan $query plan
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return array $plan 
     */
    abstract public function getPlan(&$sql, array &$errors, $options = false);

    /**
     * Checks whether a given plan execution type is supported by the implementation (true) or not (false).
     * @param string $planType type of the plan
     * @return bool
     */
    public function supportsPlanType($planType) {
        if (in_array($planType, static::$planTypes)) {
            return true;
        } else {
            return false;
        }
    }

}
