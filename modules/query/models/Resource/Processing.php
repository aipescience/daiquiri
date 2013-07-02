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

require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/php-sql-parser.php');
require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/php-sql-creator.php');

require_once(Daiquiri_Config::getInstance()->core->libs->paqu . '/parseSqlAll.php');

/**
 * Model for the processing in the query system.
 */
class Query_Model_Resource_Processing extends Daiquiri_Model_Resource_Abstract {

    /**
     * Removes and multiline comment from the SQL query. Returns the
     * cleaned query.
     * @param string $sql
     * @return string $sql 
     */
    function removeMultilineComments($sql) {
        //get rid of comments that span multiple lines
        //Example:
        // /* bla
        // 
        // bla */
        $patterns = array(
            "#\/\*([^*/]*?(\r|\n)+)+.*?\*\/#",
            "#\/\*([^\n\r*/]*?)\*\/[^\S ]+#",
            "/((--[^\n\r]*)|(#[^\n\r]*))/",
        );
        $replacements = array('', '', '');
        $newSql = preg_replace($patterns, $replacements, $sql);

        return $newSql;
    }

    /**
     * Splits an SQL query into multiple lines. Returns an array with a
     * query in each line. This will also remove the ';' from each query
     * @param string $sql
     * @return array multiline queries 
     */
    public function splitQueryIntoMultiline($sql, array &$errors) {
        $multilines = explode(";", str_replace("\n", " ", rtrim($sql, " ")));

        //remove empty entry at the end
        if (strlen($multilines[count($multilines) - 1]) == 0)
            unset($multilines[count($multilines) - 1]);

        //join queries where the ';' was located in quotes
        $cleanMultiline = array();
        $oldKey = false;
        foreach ($multilines as $key => $currSql) {
            if ($oldKey === false) {
                $oldKey = $key;
                $quoteCount = substr_count($currSql, '"') + substr_count($currSql, "'") + substr_count($currSql, '`');

                $cleanMultiline[$key] = $currSql . ";";
            } else {
                $quoteCount += substr_count($currSql, '"') + substr_count($currSql, "'") + substr_count($currSql, '`');

                $cleanMultiline[$oldKey] .= $currSql . ";";
            }

            //balance check
            if ($quoteCount % 2 == 0) {
                $oldKey = false;
            }
        }

        foreach ($cleanMultiline as $key => $currSql) {
            $currSql = trim($currSql);

            if (strlen($currSql) == 0) {
                unset($cleanMultiline[$key]);
                continue;
            }

            $cleanMultiline[$key] = rtrim(trim($currSql), ';');

            if (empty($cleanMultiline[$key])) {
                $errors['multiLineError'] = "Empty query detected (look for multiple ';'s)";
                return false;
            }
        }

        return $cleanMultiline;
    }

    /**
     * Builds the SQL parse tree for every element in the multiline SQL
     * array.
     * @param array $multilineSql array holding an SQL query string for each 
     *                            multiline query
     * @param array &$error       return array for error output
     * @return array multiline parse tree 
     */
    function multilineParseTree($multilineSql, array &$error) {
        $parseTrees = array();

        // get the resource
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);
        $adapter = $resource->getTable()->getAdapter();

        foreach ($multilineSql as $key => $currSql) {
            try {
                $tmpParseObj = processQueryWildcard($currSql, false, $adapter);
            } catch (UnsupportedFeatureException $e) {
                $error['parseError'] = $e->getMessage();
                return false;
            } catch (UnableToCalculatePositionException $e) {
                $error['parseError'] = "Error parsing SQL: You have an syntax error near the position indicated by the following parser error: " . $e->getMessage();
                return false;
            } catch (Exception $e) {
                //continue if we could not find the table. this is not that bad and parse tree is still needed
                //by paqu
                if(strpos($e->getMessage(), "42S02") === false) {
                    $error['parseError'] = $e->getMessage();
                    return false;
                }
            }

            $parseTrees[$key] = $tmpParseObj->parsed;
        }

        return $parseTrees;
    }

    /**
     * Using the parse tree, determines for each multiline the currently used
     * database.
     * 
     * If default database is given, this will be used as initial database.
     * @param array $parseTrees array holding SQL parse trees for each multiline
     *                          query
     * @param string $db initial database
     * @return array current database for each multiline
     */
    function multilineUsedDB($parseTrees, $db = false) {
        $currDB = $db;
        $multilineDBArray = array();

        foreach ($parseTrees as $key => $currTree) {
            // check if this command was a use command and register in array
            if (is_array($currTree) && array_key_exists('USE', $currTree)) {
                $currDB = trim($parseTrees[$key]['USE'][1], "` ");
            } else if (is_array($currTree) && !empty($currTree[0]) && $currTree[0] === "USE") {
                $currDB = trim($currTree[1], "` ");
            }

            $multilineDBArray[$key] = $currDB;
        }

        return $multilineDBArray;
    }

    /**
     * Add the create table statement for daiquiri query management
     * If the result table placeholder has been specified in the query, the
     * replacement will be done at that place. Otherwise, the last SELECT statement
     * in the query list will be assumed as the final result query.
     * 
     * Only adds the create table stuff if the specific query adaptor asks for it.
     * 
     * In case of SHOW, the output will be written into the table.
     * 
     * In case of empty resultset generating queries, a table will be generated that
     * holds a success message.
     * @param array $multiLineSQL array with one multiline SQL string in each element
     * @param array $multiLineParseTrees multiline SQL parse trees
     * @param string result database name
     * @param string table name where to add results to
     * @param error message
     * @return array holding queries with added create table, else FALSE
     */
    function addCreateTableStatement(&$multiLineSQL, &$multiLineParseTrees, $resultDB, #
            $resultTableName, array &$error) {

        $placeHolder = Daiquiri_Config::getInstance()->query->resultTable->placeholder;
        $foundPlaceHolder = false;
        $placeHolderKey = false;
        $lastSelectKey = false;
        $lastOtherKey = false;
        $lastKey = "";

        if (empty($resultTableName)) {
            throw new Exception("Empty result table");
        }

        $result = array();

        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);
        $engine = Daiquiri_Config::getInstance()->query->userDb->engine;

        if (empty($engine)) {
            $engine = "InnoDB";
        }

        //if the adaptor does not ask for adding the CREATE TABLE, just return input
        if ($resource::$needsCreateTable === FALSE) {
            return $multiLineSQL;
        }

        //check if placeholder is present and only once present...
        foreach ($multiLineSQL as $key => $query) {
            $placeHolderPos = strpos($query, $placeHolder);
            if ($placeHolderPos !== false) {
                //check if there is another placeholder in this string
                if (strpos($query, $placeHolder, $placeHolderPos + 1) !== false || $foundPlaceHolder === true) {
                    $error['addTableError'] = "Multiple result table placeholders found. This is not yet supported!";
                    return false;
                }

                $foundPlaceHolder = true;
                $placeHolderKey = $key;
            } else {
                $lowerCaseQuery = strtolower($query);
                if (strpos($lowerCaseQuery, "select") !== false) {
                    //sanity check
                    if (array_key_exists("SELECT", $multiLineParseTrees[$key]) === true) {
                        $lastSelectKey = $key;
                    }
                }

                if (strpos($lowerCaseQuery, "show") !== false && $lastSelectKey === false) {
                    //sanity check
                    if (array_key_exists("SHOW", $multiLineParseTrees[$key]) === true) {
                        //REWRITE SHOW TABLES
                        $lastSelectKey = $key;
                    }
                }

                if (strpos($lowerCaseQuery, "create") !== false && $lastSelectKey === false) {
                    //sanity check
                    if (array_key_exists("CREATE", $multiLineParseTrees[$key]) === true) {
                        $lastOtherKey = $key;
                        $lastKey = "CREATE";
                    }
                }

                if (strpos($lowerCaseQuery, "drop") !== false && $lastSelectKey === false) {
                    //sanity check
                    if (array_key_exists("DROP", $multiLineParseTrees[$key]) === true) {
                        $lastOtherKey = $key;
                        $lastKey = "DROP";
                    }
                }

                if (strpos($lowerCaseQuery, "alter") !== false && $lastSelectKey === false) {
                    //sanity check
                    if (array_key_exists("ALTER", $multiLineParseTrees[$key]) === true) {
                        $lastOtherKey = $key;
                        $lastKey = "ALTER";
                    }
                }

                if (strpos($lowerCaseQuery, "insert") !== false && $lastSelectKey === false) {
                    //sanity check
                    if (array_key_exists("INSERT", $multiLineParseTrees[$key]) === true) {
                        $lastOtherKey = $key;
                        $lastKey = "INSERT";
                    }
                }
            }
        }

        //if nothing has been found, just return the queries
        foreach ($multiLineSQL as $key => $query) {
            $result[$key] = $query;
        }

        if ($placeHolderKey !== false) {
            $query = $multiLineSQL[$placeHolderKey];

            $query = str_replace($placeHolder, '', $query);

            $result[$placeHolderKey] = 'SET @i = 0; CREATE TABLE `' . $resultDB . '`.`' . $resultTableName . '` ' .
                    'ENGINE=' . $engine . ' ' .
                    '(SELECT @i:=@i+1 AS `row_id`,`result`.* FROM ('
                    . $query . ') as `result`)';
        } else if ($lastSelectKey !== false) {
            $query = $multiLineSQL[$lastSelectKey];

            $result[$lastSelectKey] = 'SET @i = 0; CREATE TABLE `' . $resultDB . '`.`' . $resultTableName . '`' .
                    'ENGINE=' . $engine . ' ' .
                    '(SELECT @i:=@i+1 AS `row_id`,`result`.* FROM ('
                    . $query . ') as `result`)';
        } else if ($lastOtherKey !== false) {
            $query = $multiLineSQL[$lastOtherKey];

            $quoteQuery = $resource->getTable()->getAdapter()->quote($query);
            unset($resource);

            $result[$lastOtherKey] = $query . '; SET @i = 0; CREATE TABLE `' . $resultDB . '`.`' . $resultTableName . '`' .
                    'ENGINE=' . $engine . ' ' .
                    '(SELECT @i:=@i+1 AS `row_id`, "SUCCESS" as `status`, "' .
                    $quoteQuery . '" as `query`)';
        }

        return $result;
    }

    /**
     * Rewrites show command to use the inforamtion schema facility. More of a 
     * quick hack thing though...
     * @param array $multiLines multiline SQL 
     * @param array $multiLineParseTrees multiline SQL parse trees
     * @param array $multiLineUsedDBs multiline SQL used databases
     * @param array $outMultiLineSQL output array with one multiline SQL string in each element
     * @param array $outMultiLineParseTrees output multiline SQL parse trees
     * @param array &$errors output errors
     * @return TRUE if ok, FALSE if not
     */
    function rewriteShow(&$multiLines, &$multiLineParseTrees, &$multiLineUsedDBs, &$outMultiLineSQL, &$outMultiLineParseTrees, array &$errors) {

        foreach ($multiLineParseTrees as $key => $currNode) {
            $outMultiLineSQL[$key] = $multiLines[$key];
            $outMultiLineParseTrees[$key] = $multiLineParseTrees[$key];

            //check if SHOW is present
            if (array_key_exists("SHOW", $currNode)) {
                if (strtolower($currNode['SHOW'][1]) === "tables") {
                    //retrieve remaining statements to construct the SELECT accordingly
                    unset($currNode['SHOW'][0]);
                    unset($currNode['SHOW'][1]);

                    $attribute = implode("", $currNode['SHOW']);

                    //construct select node instead
                    $db = $multiLineUsedDBs[$key];
                    $outMultiLineSQL[$key] = "select TABLE_NAME from information_schema.tables where TABLE_SCHEMA=\"{$db}\" {$attribute}";

                    $tmpParseTree = new PHPSQLParser($outMultiLineSQL[$key], true);
                    $outMultiLineParseTrees[$key] = $tmpParseTree->parsed;
                } else if (strtolower($currNode['SHOW'][1]) === "databases") {
                    //retrieve remaining statements to construct the SELECT accordingly
                    unset($currNode['SHOW'][0]);
                    unset($currNode['SHOW'][1]);

                    $attribute = implode("", $currNode['SHOW']);

                    //construct select node instead
                    $db = $multiLineUsedDBs[$key];
                    $outMultiLineSQL[$key] = "select SCHEMA_NAME from information_schema.schemata {$attribute}";

                    $tmpParseTree = new PHPSQLParser($outMultiLineSQL[$key], true);
                    $outMultiLineParseTrees[$key] = $tmpParseTree->parsed;
                } else {
                    $errors['rewriteError'] = "No permission to use the SHOW command you specified.";
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates the SQL syntax on the server. Proxy for different databases
     * (handles only MySQL at the moment...)
     * @param string $sql
     * @param string $db
     * @param array &$errors  errors output
     * @return TRUE if ok, FALSE if not
     */
    function validateSQLServerSide($sql, $db, array &$errors) {
        return $this->validateSQLServerSideMYSQL($sql, $db, $errors);
    }

    /**
     * Server sided SQL validation for MySQL using the PaQu Validate SQL functions.
     * Checks if it is installed and returns error if not.
     * @param string $sql
     * @param string $db
     * @param array &$errors  errors output
     * @return TRUE if ok, FALSE if not
     * @throws Exception unavailable paqu_validateSQL plugin
     */
    function validateSQLServerSideMYSQL($sql, $db, array &$errors) {
        // get the resource
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);

        //check if PaQu Validate plugin is installed.
        $resource->getTable()->getAdapter()->setFetchMode(Zend_Db::FETCH_ASSOC);

        try {
            $pluginAvail = $resource->plainQuery('select name from mysql.func where name="paqu_validateSQL";');
        } catch (Exception $e) {
            throw new Exception('PaQu Validate SQL plugin not installed.');
        }

        if ($pluginAvail[0]['name'] !== "paqu_validateSQL") {
            throw new Exception('PaQu Validate SQL plugin not installed.');
        }

        $sql = trim($sql);

        $resource->getTable()->getAdapter()->setFetchMode(Zend_Db::FETCH_ASSOC);
        $sqlStr = $resource->getTable()->getAdapter()->quoteInto('select paqu_validateSQL(?) as a;', $sql);
        $validate = $resource->plainQuery($sqlStr);

        $errorString = $validate[0]['a'];

        //check if we are giving out any information about our setup in the error message
        $config = $resource->getTable()->getAdapter()->getConfig();
        $errorString = str_replace("'localhost'", "'XXXXXXXXXXXX'", $errorString);
        $errorString = str_replace("'{$config['host']}'", "'XXXXXXXXXXXX'", $errorString);
        $errorString = str_replace("'{$config['username']}'", "'XXXXXXXXXXXX'", $errorString);

        if (!empty($errorString)) {
            $errors['validateError'] = $errorString;
            return false;
        }

        return true;
    }

    /**
     * Check multiline query, if each SELECT in each query is balanced by a CREATE TABLE statement
     * @param array $multilineSql array holding an SQL query string for each 
     *                            multiline query
     * @return true if error, false if ok
     */
    function checkCreateTablePresence($multilineSql) {
        foreach ($multilineSql as $currSql) {
            $currSql = strtoupper($currSql);
            if (strpos($currSql, "SELECT") !== false) {
                if (strpos($currSql, "CREATE ") === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Combine multiline queries into one string, adding a ';' at the end of
     * each query
     * @param array $multilineSql array holding an SQL query string for each 
     *                            multiline query
     * @return string combined query
     */
    function combineMultiLine($multilineSql) {
        $query = "";

        foreach ($multilineSql as $currSql) {
            $query .= $currSql . "; ";
        }

        return $query;
    }

    /**
     * Given a table name, check if it already exists in the user database
     * @param table name
     * @return true or false
     */
    public function tableExists($table) {
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);

        $sql = "SHOW TABLES LIKE '{$table}';";

        try {
            $stmt = $resource->getTable()->getAdapter()->query($sql);
        } catch (Exception $e) {
            //check if this is error 1051 Unknown table
            if (strpos($e->getMessage(), "1051") === false) {
                throw $e;
            }
        }

        $rows = $stmt->fetchAll();

        if (sizeof($rows) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Will add an alias to every column that is a function. This is to workaround
     * a bug in the Zend framework that does not allow columns to have names that
     * look like functions. Will turn things like 'count(x)' or 'func(x, y)' into
     * '_count_x' and '_func_x_y'. The changes are written to a copy of the two 
     * input arrays.
     * @param array $multiLines multiline SQL 
     * @param array $multiLineParseTrees multiline SQL parse trees
     * @param array $outMultiLineSQL output array with one multiline SQL string in each element
     * @param array $outMultiLineParseTrees output multiline SQL parse trees
     * @return void
     */
    function escapeFunctions(&$multiLines, &$multiLineParseTrees, &$outMultiLineSQL, &$outMultiLineParseTrees) {
        foreach ($multiLineParseTrees as $key => $currTree) {
            $outMultiLineParseTrees[$key] = $currTree;
            $this->escapeFunctionsRec($currTree, $outMultiLineParseTrees[$key]);

            $sqlCreator = new PHPSQLCreator();

            try {
                $outMultiLineSQL[$key] = $sqlCreator->create($outMultiLineParseTrees[$key]);
            } catch (UnsupportedFeatureException $e) {
                $outMultiLineSQL[$key] = $multiLines[$key];
            } catch (UnableToCreateSQLException $e) {
                $outMultiLineSQL[$key] = $multiLines[$key];
            }
        }
    }

    /**
     * Function that will recursively escape all functions and aggregates.
     * @param array $inNode SQL parse tree node
     * @param array $outNode a copy of inNode that should already be present. Will
     *                       altered to reflect the escaping
     * @return void
     */
    function escapeFunctionsRec(&$inNode, &$outNode) {
        foreach ($inNode as $key => $currNode) {
            //expr_type is the usual key, however in INSERT there exists the key table... therefore check
            //for both
            if (is_array($currNode) && (array_key_exists('expr_type', $currNode) ||
                    array_key_exists('table', $currNode))) {
                //this is not a SQL top statement node
                //only do some checking if this is a subquery
                if (array_key_exists('sub_tree', $currNode)) {
                    //only go into the sub_tree if this is not a function...
                    if (!empty($currNode['sub_tree']) &&
                            $currNode['expr_type'] !== "aggregate_function" &&
                            $currNode['expr_type'] !== "function") {
                        $this->escapeFunctionsRec($currNode['sub_tree'], $outNode[$key]['sub_tree']);
                    }
                }

                //@TODO: ADD ESCAPING HERE!!
                //find functions
                if ($currNode['expr_type'] === "aggregate_function" ||
                        $currNode['expr_type'] === "function") {

                    //we only need to escape, if this is not a function of a constant value.
                    //therefore check for constants and break if only constants are found
                    $foundOtherThanConst = true;
                    if(!empty($currNode['sub_tree'])) {
                        //setting it false, since we are actually checking - true value above is just a dummy
                        //to continue processing
                        $foundOtherThanConst = false;
                        foreach($currNode['sub_tree'] as $node) {
                            if($node['expr_type'] !== 'const' &&
                                $node['expr_type'] !== 'operator') {

                                $foundOtherThanConst = true;
                                break;
                            }
                        }
                    }

                    //check if an alias is already set
                    if ($foundOtherThanConst === true && (!array_key_exists("alias", $currNode) ||
                                                            $currNode['alias'] === false)) {
                        //build escaped string
                        $escapedString = "_" . $this->buildEscapedString(array($currNode));

                        $alias = array("as" => true,
                            "name" => "`{$escapedString}`",
                            "base_expr" => "as `{$escapedString}`",
                            "position" => 0);

                        $outNode[$key]['alias'] = $alias;
                    }
                }
            } else if (is_array($currNode)) {
                //this is a top SQL statement node like SELECT or FROM
                $this->escapeFunctionsRec($currNode, $outNode[$key]);
            } else {
                //this is an end leaf...
                continue;
            }
        }
    }

    /**
     * Function that will recursively go through the branch at the function to
     * construct the escaped column name
     * @param array $inNode SQL parse tree node
     * @return string parts of the escaped function name
     */
    function buildEscapedString($inNode) {
        $str = "";

        foreach ($inNode as $currNode) {
            $partStr = "";

            if (array_key_exists("sub_tree", $currNode) && $currNode["sub_tree"] !== false) {
                $partStr = $this->buildEscapedString($currNode["sub_tree"]);
            }

            $partStr = str_replace(".", "__", $partStr);

            if ($currNode["expr_type"] === "aggregate_function" ||
                    $currNode['expr_type'] === "function") {
                $str .= $currNode["base_expr"] . "_" . $partStr;        #last "_" already added below
            } else {
                $str .= $currNode["base_expr"] . "_";
            }
        }

        return $str;
    }

}
