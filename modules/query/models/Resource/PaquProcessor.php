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

require_once(Daiquiri_Config::getInstance()->core->libs->paqu . '/parallelQuery.php');

class Query_Model_Resource_PaquProcessor extends Query_Model_Resource_AbstractProcessor {

    public static $planTypes = array("QPROC_SIMPLE", "QPROC_INFOPLAN", "QPROC_ALTERPLAN");

    /**
     * Construtor. Sets table. 
     */
    public function __construct() {
        parent::__construct();

        $this->paraQuery = new ParallelQuery();

        $config = Daiquiri_Config::getInstance();

        //check if server is a PaQu enabled server...
        $adapter = $this->getUserDBResource()->getTable()->getAdapter();

        //check if PaQu spider plugin is installed.
        $adapter->setFetchMode(Zend_Db::FETCH_ASSOC);
        $pluginAvail = $adapter->fetchAll('select name from mysql.func where name="spider_bg_direct_sql";');

        if (empty($pluginAvail) || $pluginAvail[0]['name'] !== "spider_bg_direct_sql") {
            //    throw new Exception('PaQu spider engine setup not correct.');
        }

        //set options in parallel query object
        $this->paraQuery->setEngine($config->query->userDb->engine);
        $this->paraQuery->setDB($config->query->scratchdb);
        $this->paraQuery->setConnectOnServerSite($config->query->processor->paqu->serverConnectStr);
        $this->paraQuery->setSpiderUsr($config->query->processor->paqu->spiderUsr);
        $this->paraQuery->setSpiderPwd($config->query->processor->paqu->spiderPwd);

        //set the tables that only reside on the head node
        $listOfHeadNodeTables = array();

        //all tables in the user db are head node tables, therefore add the database name and a list
        //of all tables in the database
        $listOfHeadNodeTables[] = $this->resultDB;

        //get list of tables in user database
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);

        $sql = "SHOW TABLES;";
        $stmt = $resource->getTable()->getAdapter()->query($sql);
        $rows = $stmt->fetchAll();

        foreach($rows as $row) {
            $listOfHeadNodeTables[] = array_shift($row);
        }

        $this->paraQuery->setHeadNodeTables($listOfHeadNodeTables);

        if(empty($config->query->processor->paqu->federated)) {
            $this->paraQuery->setFedEngine("FEDERATED");
        } else {
            $this->paraQuery->setFedEngine($config->query->processor->paqu->federated);
        }
    }

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

        $multiLineUsedDBs = $this->processing->multilineUsedDB($multiLineParseTrees, $this->resultDB);

        //we are not permitting "USE"!
        foreach ($multiLineParseTrees as $key => $currTree) {
            // check if this command was a use command and register in array
            if (is_array($currTree) && array_key_exists('USE', $currTree)) {
                $errors['illegalSQL'] = "'USE' SQL command currently not supported.";
                return false;
            } else if (is_array($currTree) && !empty($currTree[0]) && $currTree[0] === "USE") {
                $errors['illegalSQL'] = "'USE' SQL command currently not supported.";
                return false;
            }
        }

        $multiLineParseTrees = $this->processing->multilineProcessQueryWildcard($multiLineParseTrees, $errors);

        if (!empty($errors)) {
            return false;
        }

        //check ACLs
        if ($this->permissions->check($multiLineParseTrees, $multiLineUsedDBs, $errors) === false) {
            return false;
        }

        //check if table already exists
        if ($table !== null && $this->processing->tableExists($table)) {
            $errors['submitError'] = "Table '{$table}' already exists";
            return false;
        }

        //paqu does not yet support multiline queries, so raise an error
        if(count($multiLineUsedDBs) > 1) {
            $errors['submitError'] = "Multiple queries are not supported with PaQu.";
            return false;
        }

        //combine multiline queries into one
        $combinedQuery = $this->processing->combineMultiLine($multiLines);

        //validate sql on server
        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            if ($this->processing->validateSQLServerSide($combinedQuery, $this->resultDB, $errors) !== true) {
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
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->processing->removeMultilineComments($plan);
        $multiLines = $this->processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return false;
        }

        $plan = $multiLines;

        $multiLineParseTrees = $this->processing->multilineParseTree($multiLines, $errors);

        if (!empty($errors)) {
            foreach($errors as $error) {
                //check on an nonexisting temp table should be ignored here
                if(strpos($error, "42S02") === false) {
                    return false;
                }
            }
        }

        $listCreateTmpTables = array();
        $listLinkTmpTables = array();
        $listDropTmpTables = array();
        if (!$this->_checkPaquCallSyntax($multiLineParseTrees, $errors, $listCreateTmpTables, 
                    $listLinkTmpTables, $listDropTmpTables)) {
            return false;
        }

        if (!$this->_checkPaquDropTmpBalancing($listCreateTmpTables, $listLinkTmpTables, $listDropTmpTables, $errors)) {
            return false;
        }

        if (!$this->_checkPaquPermissionsAndSyntax($multiLineParseTrees, $multiLines, $errors)) {
            return false;
        }

        if (!$this->_checkResTableTagPresence($multiLines, $errors)) {
            return false;
        }

        return true;
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

        if ($plan === false) {
            $errors[] = "Paqu no plan submitted! This should not happen at all!";
            return false;
        } else {
            //replace result table tag with real result table

            //determine result table name
            if (empty($resultTableName)) {
                $micro = explode(" ", microtime());
                $resultTableName = date("Y-m-d\TH:i:s") . ":" . substr($micro[0], 2, 4);
            }

            foreach ($plan as &$line) {
                $line = str_replace(Daiquiri_Config::getInstance()->query->resultTable->placeholder, $resultTableName, $line);
            }

            $this->paraQuery->setParallelQueryPlan($plan);
            $this->paraQuery->translateQueryPlan();

            $queries = $this->paraQuery->getActualQueries();

            $combinedQuery = $this->processing->combineMultiLine($queries);

            $combinedQuery = str_replace(";;", ";", $combinedQuery);
        }

        //build job object
        $job = array(
            'table' => $resultTableName,
            'database' => $this->resultDB,
            'host' => false,
            'query' => $sql,
            'actualQuery' => $plan,
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
        $errors = array();

        // preprocess string
        $noMultilineCommentSQL = $this->processing->removeMultilineComments($sql);
        $multiLines = $this->processing->splitQueryIntoMultiline($noMultilineCommentSQL, $errors);

        if ($multiLines === false) {
            return array();
        }

        $this->paraQuery->setCheckOnDB(false);
        $this->paraQuery->setAddRowNumbersToFinalTable(true);

        $adapter = $this->getUserDBResource()->getTable()->getAdapter();

        // get current user
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        if ($username === null) {
            $username = 'Guest';
        }

        $dummyTableName = Daiquiri_Config::getInstance()->getUserDBName($username) . ".`" . Daiquiri_Config::getInstance()->query->resultTable->placeholder . "`";

        try {
            $this->paraQuery->setSQL($noMultilineCommentSQL, $adapter);
            $this->paraQuery->generateParallelQueryPlan($dummyTableName);
        } catch (Exception $err) {
            $errors[] = "Paqu Error: " . $err->getMessage();
            return array();
        }

        $resultStr = $this->_formatPlan($this->paraQuery->getParallelQueryPlan());

        return $resultStr;
    }

    /**
     * Takes the output of the explan extended query, and formats it nicely
     * 
     * @param array plan
     * @return array string with formatted plan
     */
    private function _formatPlan(&$plan) {
        $result = array();

        foreach ($plan as $key => $row) {
            $result[] = $row . ";";
        }

        return $result;
    }

    private function _checkPaquCallSyntax($parseTrees, array &$errors, array &$listCreateTmpTables, array &$listLinkTmpTables, array &$listDropTmpTables) {
        foreach ($parseTrees as $query) {
            foreach ($query as $key => $node) {
                if ($key === "CALL") {
                    //check if this is a paqu related call
                    if (substr_compare($node[0], "paqu", 0, 4) == 0) {
                        if ($node[0] === "paquExec") {
                            //count parameters
                            if (!empty($node[1])) {
                                preg_match("/\(\s*\"(.+)\"\s*,\s*\"(.+)\"\s*\)/", $node[1], $res);
                                if (count($res) != 3 || strpos($res[1], '"') !== false) {
                                    $errors[] = "paquExec needs two parameters.";
                                    return false;
                                }

                                $listCreateTmpTables[] = $res[2];
                            } else {
                                $errors[] = "paquExec is a procedure and needs two parameters";
                                return false;
                            }
                        } else if ($node[0] === "paquLinkTmp") {
                            //count parameters
                            if (!empty($node[1])) {
                                preg_match("/\(\s*\"(.+)\"\s*\)/", $node[1], $res);
                                if (count($res) != 2 || strpos($res[1], '"') !== false) {
                                    $errors[] = "paquLinkTmp needs one parameters.";
                                    return false;
                                }

                                $listLinkTmpTables[] = $res[1];
                            } else {
                                $errors[] = "paquDropTmp is a procedure and needs one parameters";
                                return false;
                            }
                        } else if ($node[0] === "paquDropTmp") {
                            //count parameters
                            if (!empty($node[1])) {
                                preg_match("/\(\s*\"(.+)\"\s*\)/", $node[1], $res);
                                if (count($res) != 2 || strpos($res[1], '"') !== false) {
                                    $errors[] = "paquDropTmp needs one parameters.";
                                    return false;
                                }

                                $listDropTmpTables[] = $res[1];
                            } else {
                                $errors[] = "paquDropTmp is a procedure and needs one parameters";
                                return false;
                            }
                        } else {
                            $errors[] = "Paqu related call not paquExec, paquLinkTmp, or paquDropTmp.";
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _checkPaquDropTmpBalancing(array &$listCreateTmpTables, array &$listLinkTmpTables, array &$listDropTmpTables, array &$errors) {
        if ((count($listCreateTmpTables) + count($listLinkTmpTables)) !== count($listDropTmpTables)) {
            $errors[] = "Paqu temporary tables are not balanced by paquDropTmp calls.";
            return false;
        }

        foreach ($listCreateTmpTables as $value) {
            $keys = array_keys($listDropTmpTables, $value);
            if (count($keys) !== 1) {
                $errors[] = "Paqu temporary tables are not exactly balanced by paquDropTmp calls.";
                return false;
            }
        }

        foreach ($listLinkTmpTables as $value) {
            $keys = array_keys($listDropTmpTables, $value);
            if (count($keys) !== 1) {
                $errors[] = "Paqu linked temporary tables are not exactly balanced by paquDropTmp calls.";
                return false;
            }
        }

        return true;
    }

    private function _checkPaquPermissionsAndSyntax($parseTrees, $sql, array &$errors) {
        //build array for further processing. getting rid of all paqu commands to check
        //permissions and stuff

        $queryArray = array();
        foreach ($parseTrees as $keyQuery => $query) {
            if (array_key_exists("CALL", $query)) {
                if ($query['CALL'][0] === "paquExec") {
                    //count parameters
                    if (!empty($query['CALL'][1])) {
                        preg_match("/\(\s*\"(.+)\"\s*,\s*\"(.+)\"\s*\)/", $query['CALL'][1], $res);

                        $sql[$keyQuery] = $res[1];
                        $tree = $this->processing->multilineParseTree(array($res[1]), $errors);

                        $queryArray[] = $tree[0];
                    }
                } else {
                    if ($query['CALL'][0] === "paquDropTmp") {
                        unset($sql[$keyQuery]);
                    }
                }
            } else {
                $queryArray[] = $query;
            }
        }

        //do normal permission check
        $currNode = $query; //array($node);
        $multiLineUsedDBs = $this->processing->multilineUsedDB($queryArray, $this->resultDB);

        //check ACLs
        if ($this->permissions->check($queryArray, $multiLineUsedDBs, $errors) === false) {
            return false;
        }

        //validate sql on server
        if (Daiquiri_Config::getInstance()->query->validate->serverSide) {
            $combinedSql = "";
            foreach ($sql as $part) {
                $combinedSql = $combinedSql . $part . "; ";
            }

            if ($this->processing->validateSQLServerSide($combinedSql, $this->resultDB, $errors) !== true) {
                $scratchdb = Daiquiri_Config::getInstance()->query->scratchdb;
                foreach ($errors as $error) {
                    if (strpos($error, "ERROR 1146") === false && strpos($error, $scratchdb) === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function _checkResTableTagPresence($multiLines, array &$errors) {
        $countTags = 0;

        foreach ($multiLines as $line) {
            if (strpos($line, Daiquiri_Config::getInstance()->query->resultTable->placeholder) !== false) {
                $countTags += 1;
            }
        }

        if ($countTags !== 1) {
            $errors[] = "Paqu no result table tag found. Please add " . Daiquiri_Config::getInstance()->query->resultTable->placeholder . " at the position where the result table is created"; #
            return false;
        }

        return true;
    }

}
