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

class Query_Model_Resource_DirectProcessor extends Query_Model_Resource_AbstractProcessor {

    public static $planTypes = array("QPROC_SIMPLE");

    /**
     * Validates a raw query before any processing and altering of the query occurred.
     * 
     * @param string query
     * @param string result table name
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return TRUE if valid, FALSE if not
     */
    public function validateQuery($sql, $table, array &$errors, $options = false) {
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->processing->removeMultilineComments($sql);
        $multiLines = $this->processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return false;
        }

        $multiLineParseTrees = $this->processing->multilineParseTree($multiLines, $errors);

        if (!empty($errors)) {
            return false;
        }

        // get current user
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        if ($username === null) {
            $username = 'Guest';
        }

        $resultDB = Daiquiri_Config::getInstance()->getUserDBName($username);

        $multiLineUsedDBs = $this->processing->multilineUsedDB($multiLineParseTrees, $resultDB);

        //check ACLs
        if ($this->permissions->check($multiLineParseTrees, $multiLineUsedDBs, $errors) === false) {
            return false;
        }

        //check if table already exists
        if ($table !== null && $this->processing->tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        //escape function column names
        $escapedMultiLine = false;
        $escapedMultiLineParseTrees = false;
        $this->processing->escapeFunctions($multiLines, $multiLineParseTrees, $escapedMultiLine, $escapedMultiLineParseTrees);

        //combine multiline queries into one
        $combinedQuery = $this->processing->combineMultiLine($escapedMultiLine);

        //validate sql on server
        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            if ($this->processing->validateSQLServerSide($combinedQuery, $resultDB, $errors) !== true) {
                return false;
            }
        }

        return true;
    }

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
    public function validatePlan(&$plan, $table, array &$errors, $options = false) {
        return TRUE;
    }

    /**
     * Prepares a job object according to the query plan (if supported), otherwise just prepares a job 
     * according to the processed query (without plan, depending on implementation)
     * 
     * @param array query
     * @param array errors holding any error that occurs
     * @param array plan
     * @param string result table name
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return object job
     */
    public function query(&$sql, array &$errors, &$plan = false, $resultTableName = false, $options = false) {
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->processing->removeMultilineComments($sql);
        $multiLines = $this->processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return false;
        }

        $multiLineParseTrees = $this->processing->multilineParseTree($multiLines, $errors);

        if (!empty($errors)) {
            return false;
        }

        // get current user
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        if ($username === null) {
            $username = 'Guest';
        }

        $resultDB = Daiquiri_Config::getInstance()->getUserDBName($username);

        $multiLineUsedDBs = $this->processing->multilineUsedDB($multiLineParseTrees, $resultDB);

        //rewrite show statements
        $showRewrittenMultiLine = false;
        $showRewrittenMultiLineParseTrees = false;
        if ($this->processing->rewriteShow($multiLines, $multiLineParseTrees, $multiLineUsedDBs, $showRewrittenMultiLine, $showRewrittenMultiLineParseTrees, $errors) !== true) {
            return false;
        }

        //escape function column names
        $escapedMultiLine = false;
        $escapedMultiLineParseTrees = false;
        $this->processing->escapeFunctions($showRewrittenMultiLine, $showRewrittenMultiLineParseTrees, $escapedMultiLine, $escapedMultiLineParseTrees);

        //add create table statements
        //determine result table name
        if (empty($resultTableName)) {
            $micro = explode(" ", microtime());
            $resultTableName = date("Y-m-d\TH:i:s") . ":" . substr($micro[0], 2, 4);
        }

        $querySQL = $this->processing->addCreateTableStatement($escapedMultiLine, $escapedMultiLineParseTrees, $resultDB, $resultTableName, $errors);
        if (array_key_exists('addTableError', $errors)) {
            return false;
        }

        //check if every SELECT statement in the query, has been wrapped by a CREATE TABLE statement. We only do
        //this automatically for the last SELECT statement, the others have to be treated by the user
        //If this would not be done, some queries end up in nirvana and that might upset the database server...
        if ($this->processing->checkCreateTablePresence($querySQL) === FALSE) {
            $errors['createTableError'] = "Not every SELECT statement is balanced with a CREATE TABLE statements. Queries end up delivering results to nowhere.";
            return false;
        }

        //combine multiline queries into one
        $combinedQuery = $this->processing->combineMultiLine($querySQL);

        //build job object
        $job = array(
            'table' => $resultTableName,
            'database' => $resultDB,
            'host' => false,
            'query' => $sql,
            'actualQuery' => $combinedQuery,
            'fullActualQuery' => $combinedQuery, //this is set, if we want to use a query we don't want to show the user
            'user_id' => false,
            'status_id' => false,
            'time' => false
        );
        return $job;
    }

    /**
     * Returns the query plan depending on implementation. If an implementation does not support query
     * plans, this needs to return an empty array.
     * 
     * @param array query
     * @param array errors holding any error that occurs
     * @param array options any options that a specific implementation of validateQuery needs to get
     * @return plan 
     */
    public function getPlan(&$sql, array &$errors, $options = false) {
        return array();
    }

}
