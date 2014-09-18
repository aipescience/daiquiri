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

class Query_Model_Resource_DirectProcessor extends Query_Model_Resource_AbstractProcessor {

    /**
     * Plan types. This can be either QPROC_SIMPLE, QPROC_INFOPLAN, QPROC_ALTERPLAN
     * @var string $planTypes
     */
    public static $planTypes = array("QPROC_SIMPLE");

    /**
     * Validates a raw query before any processing and altering of the query occurred.
     * @param string $sql query string
     * @param string $table name of the job's table
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return bool $success
     */
    public function validateQuery($sql, $table, array &$errors, $options = false) {
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->_processing->removeMultilineComments($sql);
        $multiLines = $this->_processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return false;
        }

        $multiLineParseTrees = $this->_processing->multilineParseTree($multiLines, $errors);

        if (!empty($errors)) {
            return false;
        }

        $multiLineUsedDBs = $this->_processing->multilineUsedDB($multiLineParseTrees, $this->_userDb);

        $multiLineParseTrees = $this->_processing->multilineProcessQueryWildcard($multiLineParseTrees, $multiLineUsedDBs, $errors);

        if (!empty($errors)) {
            return false;
        }

        // check ACLs
        if ($this->_permissions->check($multiLineParseTrees, $multiLineUsedDBs, $errors) === false) {
            return false;
        }

        // check if table already exists
        if ($table !== null && $this->_processing->tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        // combine multiline queries into one
        $combinedQuery = $this->_processing->combineMultiLine($multiLines);

        // validate sql on server
        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            if ($this->_processing->validateSQLServerSide($combinedQuery, $this->_userDb, $errors) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a query plan (if alterable) before submission of the query.
     * @param array $plan $query plan
     * @param string $table name of the job's table
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return bool $success
     */
    public function validatePlan(&$plan, $table, array &$errors, $options = false) {
        return true;
    }

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
    public function query(&$sql, array &$errors, &$plan = false, $resultTableName = false, $options = false) {
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->_processing->removeMultilineComments($sql);
        $multiLines = $this->_processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return false;
        }

        $multiLineParseTrees = $this->_processing->multilineParseTree($multiLines, $errors);

        if (!empty($errors)) {
            return false;
        }

        $multiLineUsedDBs = $this->_processing->multilineUsedDB($multiLineParseTrees, $this->_userDb);

        $multiLineParseTrees = $this->_processing->multilineProcessQueryWildcard($multiLineParseTrees, $multiLineUsedDBs, $errors);

        if (!empty($errors)) {
            return false;
        }

        // rewrite show statements
        $showRewrittenMultiLine = false;
        $showRewrittenMultiLineParseTrees = false;
        if ($this->_processing->rewriteShow($multiLines, $multiLineParseTrees, $multiLineUsedDBs, $showRewrittenMultiLine, $showRewrittenMultiLineParseTrees, $errors) !== true) {
            return false;
        }

        // add create table statements
        // determine result table name
        if (empty($resultTableName)) {
            $resultTableName = $this->createResultTableName();
        }

        $querySQL = $this->_processing->addCreateTableStatement($showRewrittenMultiLine, $showRewrittenMultiLineParseTrees, $this->_userDb, $resultTableName, $errors);
        if (array_key_exists('addTableError', $errors)) {
            return false;
        }

        // check if every SELECT statement in the query, has been wrapped by a CREATE TABLE statement. We only do
        // this automatically for the last SELECT statement, the others have to be treated by the user
        // If this would not be done, some queries end up in nirvana and that might upset the database server...
        if ($this->_processing->checkCreateTablePresence($querySQL) === FALSE) {
            $errors['createTableError'] = "Not every SELECT statement is balanced with a CREATE TABLE statements. Queries end up delivering results to nowhere.";
            return false;
        }

        // combine multiline queries into one
        $combinedQuery = $this->_processing->combineMultiLine($querySQL);

        // build job object
        $job = array(
            'table' => $resultTableName,
            'database' => $this->_userDb,
            'host' => false,
            'query' => $sql,
            'actualQuery' => $combinedQuery,
            'fullActualQuery' => $combinedQuery, // this is set, if we want to use a query we don't want to show the user
            'user_id' => false,
            'status_id' => false,
            'time' => false
        );
        return $job;
    }

    /**
     * Returns the query plan depending on implementation. If an implementation does not support query
     * plans, this needs to return an empty array.
     * @param array $plan $query plan
     * @param array $errors array holding any errors that occur
     * @param array $options any options that a specific implementation of validateQuery needs to get
     * @return array $plan 
     */
    public function getPlan(&$sql, array &$errors, $options = false) {
        return array();
    }

}
