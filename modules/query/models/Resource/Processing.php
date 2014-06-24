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

if(!class_exists("\PHPSQLParser\PHPSQLCreator"))
    require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/PHPSQLCreator.php');

if(!class_exists("\PHPSQLParser\PHPSQLParser"))
    require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/PHPSQLParser.php');

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
        // get rid of comments that span multiple lines
        // Example:
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

        foreach ($multilineSql as $key => $currSql) {
            try {
                $tmpTree = new \PHPSQLParser\PHPSQLParser($currSql);
            } catch (Exception $e) {
                $error['parseError'] = $e->getMessage();
                return false;
            }

            $parseTrees[$key] = $tmpTree->parsed;
        }

        return $parseTrees;
    }

    /**
     * Builds the SQL parse tree for every element in the multiline SQL
     * array.
     * @param array $multilineSqlTree array holding an SQL query string for each 
     *                            multiline query
     * @param array &$error       return array for error output
     * @return array multiline parse tree 
     */
    function multilineProcessQueryWildcard($multilineSqlTree, $multiLineUsedDBs, array &$error) {
        // get the resource
        $resource = Query_Model_Resource_AbstractQuery::factory();
        $adapter = $resource->getAdapter();

        foreach ($multilineSqlTree as $key => $currSqlTree) {
            try {
                $tmpParseObj = $this->processQueryWildcard($currSqlTree, $multiLineUsedDBs[$key]);
            } catch (UnsupportedFeatureException $e) {
                $error['parseError'] = $e->getMessage();
                return false;
            } catch (UnableToCalculatePositionException $e) {
                $error['parseError'] = "Error parsing SQL: You have an syntax error near the position indicated by the following parser error: " . $e->getMessage();
                return false;
            } catch (Exception $e) {
                //continue if we could not find the table. this is not that bad and parse tree is still needed
                //by paqu
                if(strpos($e->getMessage(), "Syntax error or access violation") === false) {
                    $error['parseError'] = $e->getMessage();
                    return false;
                } else {
                    $error['parseError'] = "Error parsing SQL: Specified table does not exist";
                    return false;
                }
            }

            $parseTrees[$key] = $tmpParseObj;
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

        $resource = Query_Model_Resource_AbstractQuery::factory();
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

            $quoteQuery = $resource->getAdapter()->quote($query);
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
                if (strtolower($currNode['SHOW'][0]) === "tables") {
                    //retrieve remaining statements to construct the SELECT accordingly
                    $db = $multiLineUsedDBs[$key];
                    foreach($currNode['SHOW'] as $key => $node) {
                        if($node['expr_type'] === "database") {
                            $db = trim($node['name'], "`");
                            break;
                        }
                    }

                    $attribute = "";
                    $found = false;
                    foreach($currNode['SHOW'] as $node) {
                        if($node['base_expr'] === "like" || $node['base_expr'] === "where" || $found === true) {
                            $found = true;

                            if($attribute === "") {
                                $attribute = "AND ";
                                continue;
                            }

                            $attribute .= $node['base_expr'] . " ";
                        }
                    }

                    //construct select node instead
                    $outMultiLineSQL[$key] = "select TABLE_NAME from information_schema.tables where TABLE_SCHEMA=\"{$db}\" {$attribute}";

                    $tmpParseTree = new \PHPSQLParser\PHPSQLParser($outMultiLineSQL[$key], true);
                    $outMultiLineParseTrees[$key] = $tmpParseTree->parsed;
                } else if (strtolower($currNode['SHOW'][1]) === "databases") {
                    //retrieve remaining statements to construct the SELECT accordingly
                    $attribute = "";
                    $found = false;
                    foreach($currNode['SHOW'] as $node) {
                        if($node['base_expr'] === "like" || $node['base_expr'] === "where" || $found === true) {
                            $found = true;

                            if($attribute === "") {
                                $attribute = "WHERE ";
                                continue;
                            }

                            $attribute .= $node['base_expr'] . " ";
                        }
                    }

                    //construct select node instead
                    $db = $multiLineUsedDBs[$key];
                    $outMultiLineSQL[$key] = "select SCHEMA_NAME from information_schema.schemata {$attribute}";

                    $tmpParseTree = new \PHPSQLParser\PHPSQLParser($outMultiLineSQL[$key], true);
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
        $resource = Query_Model_Resource_AbstractQuery::factory();

        //check if PaQu Validate plugin is installed.
        $resource->getAdapter()->setFetchMode(Zend_Db::FETCH_ASSOC);

        try {
            $pluginAvail = $resource->getAdapter()->query('SELECT name FROM mysql.func WHERE name="paqu_validateSQL";')->fetchAll();
        } catch (Exception $e) {
            throw new Exception('PaQu Validate SQL plugin not installed.');
        }

        if ($pluginAvail[0]['name'] !== "paqu_validateSQL") {
            throw new Exception('PaQu Validate SQL plugin not installed.');
        }

        $sql = trim($sql);

        $resource->getAdapter()->setFetchMode(Zend_Db::FETCH_ASSOC);
        $conn = $resource->getAdapter()->getConnection();

        $sqlStr = $resource->getAdapter()->quoteInto('select paqu_validateSQL(?) as a;', $sql);

        try {
            $conn->exec("use " . $db);
            $validate = $conn->query($sqlStr)->fetchAll();
        } catch (Exception $e) {
            $errors['validateError'] = $e->getMessage();
            return false;
        }

        $errorString = $validate[0]['a'];

        //check if we are giving out any information about our setup in the error message
        $config = $resource->getAdapter()->getConfig();
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
        $resource = Query_Model_Resource_AbstractQuery::factory();

        $sql = "SHOW TABLES LIKE '{$table}';";

        try {
            $stmt = $resource->getAdapter()->query($sql);
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

            $sqlCreator = new \PHPSQLParser\PHPSQLCreator();

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
                            "no_quotes" => array(
                                    "delim" => ".",
                                    "parts" => array($escapedString)
                                ),
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

    /**
     * @brief Recursive function that acts on a node of the SQL tree to process the
     *    SQL query.
     * @param sqlTree SQL tree
     * @param default database as defined by USE clause
     * @return a new SQL parser tree with the resolved columns
     * 
     * This recursive function parses a given part of the SQL tree and substitutes
     * all the SQL * attributes in subqueries in FROM, WHERE and eventually in the
     * SELECT statement.
     */
    function processQueryWildcard($sqlTree, $defaultDB = false) {
        //check if this is a SELECT statement (could be something else like a USE or something)
        if(!array_key_exists("SELECT", $sqlTree)) {
            return $sqlTree;
        }

        $this->_parseSqlAll_FROM($sqlTree, $defaultDB);
        $this->_parseSqlAll_WHERE($sqlTree);
        $this->_parseSqlAll_SELECT($sqlTree);

        //after the rewrite, go through the tree and find any name in WHERE, GROUP, ORDER
        //that needs to be changed as well
        $this->_parseSqlAll_fixAliases($sqlTree);

        //set delimiter of the last column in the SELECT statement to false
        $lastNode = array_pop($sqlTree['SELECT']);
        $lastNode['delim'] = false;
        array_push($sqlTree['SELECT'], $lastNode);

        return $sqlTree;
    }

    function _parseSqlAll_fixAliases(&$sqlTree) {
        //create list of all tables in FROM clause - link them to array with alias as key, 
        //and node as value
        $fromList = array();

        if(!is_array($sqlTree) || !array_key_exists("FROM", $sqlTree)) {
            return;
        }

        foreach($sqlTree['FROM'] as $node) {
            if($this->_isSubquery($node)) {
                $this->_parseSqlAll_fixAliases($node['sub_tree']);
            }

            if($this->_hasAlias($node)) {
                $fromList[$node['alias']['name']] = $node;
            } else {
                $fromList[$node['table']] = $node;
            }
        }

        //go through WHERE it exists
        foreach($sqlTree as $key => $sqlNode) {
            if($key !== "WHERE" && $key !== "GROUP" && $key !== "ORDER")
                continue;

            if(array_key_exists("WHERE", $sqlTree)) {
                $this->_parseSqlAll_fixAliasesInNode($sqlTree['WHERE'], $fromList);
            } 

            if (array_key_exists("GROUP", $sqlTree)) {
                $this->_parseSqlAll_fixAliasesInNode($sqlTree['GROUP'], $fromList, $sqlTree['SELECT']);
            } 

            if (array_key_exists("ORDER", $sqlTree)) {
                $this->_parseSqlAll_fixAliasesInNode($sqlTree['ORDER'], $fromList, $sqlTree['SELECT']);
            }
        }
    }

    function _parseSqlAll_fixAliasesInNode(&$sqlTree, $fromList, &$selectTreeNode = FALSE) {
        foreach($sqlTree as &$node) {
            if($this->_isSubquery($node)) {
                $this->_parseSqlAll_fixAliases($node['sub_tree']);
            }

            //only process colrefs
            if(!$this->_isColref($node) && !$this->_isExpression($node)) {
                continue;
            }

            $table = $this->_extractTableName($node);
            $column = $this->_extractColumnName($node);

            //we only need to change this column if it was retrieved from a subquery
            if($table !== false && array_key_exists($table, $fromList) && 
                $this->_isSubquery($fromList[$table]) && $fromList[$table]['sub_tree'] != NULL) {
                //look this column up in the sub select
                foreach($fromList[$table]['sub_tree']['SELECT'] as $selNode) {
                    if($this->_hasAlias($selNode) && strpos($selNode['alias']['name'], $column)) {
                            $node['base_expr'] = "`" . $table . "`.`" . trim($selNode['alias']['name'], "`") . "`";
                            $node['no_quotes'] = array("delim" => ".", "parts" => array($table, trim($selNode['alias']['name'], "`")));
                    }
                }
            } else if ($selectTreeNode !== FALSE) {
                //go through the list of columns in the select tree part, and find the corresponding alias
                //we are doing this the cheep way:
                //take the column name in where/order/group and get rid of all ` and replace . with __
                //this way we should end up with a name that should be contained in the SELECT column list
                $currAlias = str_replace(".", "__", str_replace("`", "", $node['base_expr']));
                $strLenCurrAlias = strlen($currAlias);

                foreach($selectTreeNode as $selNode) {
                    $nodeAlias = trim($selNode['alias']['name'], "`");

                    $aliasStrPos = strpos($nodeAlias, $currAlias);

                    if($aliasStrPos !== FALSE && strlen($nodeAlias) == $aliasStrPos + $strLenCurrAlias) {
                        $node['base_expr'] = $selNode['alias']['name'];
                        $node['no_quotes'] = array("delim" => ".", "parts" => array($selNode['alias']['name']));
                    }
                }
            }
        }   
    }

    /**
     * @brief Identifies subqueries that need processing in the FROM clause
     * @param sqlTree SQL parser tree node of complete query/subquery
     * @param default database as defined by USE clause
     * 
     * This function parser the current level of the sqlTree to find any subqueries
     * in the FROM statement. If subqueries are found, process them recursively using
     * processQueryWildcard.
     */
    function _parseSqlAll_FROM(&$sqlTree, $defaultDB = false) {
        if(!is_array($sqlTree) || !array_key_exists('FROM', $sqlTree))
            return;
        
        foreach($sqlTree['FROM'] as &$node) {
            if($this->_isSubquery($node) && $node['sub_tree'] != NULL) {
                $tree = $this->processQueryWildcard($node['sub_tree'], $defaultDB);
                $node['sub_tree'] = $tree;
            }
        }

        //add the default database to FROM tables, as defined in defaultDB
        if($defaultDB !== false) {
            foreach($sqlTree['FROM'] as &$node) {
                if($this->_isSubquery($node)) {
                    continue;
                }

                $tmp = $this->_parseSqlAll_parseResourceName($node['table']);
                if(count($tmp) == 2) {
                    //add the database name
                    $node['table'] = '`' . trim($defaultDB, '`') . '`.' . $node['table'];
                    $node['no_quotes'] = array("delim" => ".", "parts" => array(trim($defaultDB, '`'), $node['table']));
                }
            }
        }
    }

    /**
     * @brief Identifies subqueries that need processing in the WHERE clause
     * @param sqlTree SQL parser tree node of complete query/subquery
     * 
     * This function parser the current level of the sqlTree to find any subqueries
     * in the WHERE statement. If subqueries are found, process them recursively using
     * processQueryWildcard.
     */
    function _parseSqlAll_WHERE(&$sqlTree) {
        if(!is_array($sqlTree) || !array_key_exists('WHERE', $sqlTree))
            return;

        foreach($sqlTree['WHERE'] as &$node) {
            if($this->_isSubquery($node)) {
                $tree = $this->processQueryWildcard($node['sub_tree']);
                    $node['sub_tree'] = $tree->parsed;
            }
        }
    }

    /**
     * @brief Add all columns to the SELECT tree
     * @param sqlTree SQL parser tree node of complete query/subquery
     * 
     * This function will evaluate the all the tables that need SQL * attribute substitution.
     * The database is queried to retrieve a complete list of columns of each table and the
     * approperiate SELECT colref nodes are added to the SQL parser tree. The SQL * attribute
     * is removed from the sqlTree SELECT node.
     */
    function _parseSqlAll_SELECT(&$sqlTree) {
        if(!is_array($sqlTree) || !array_key_exists('SELECT', $sqlTree))
            return;

        $table = false;

        $selectCpy = $sqlTree['SELECT'];
        $sqlTree['SELECT'] = array();

        foreach($selectCpy as &$node) {
            if(strpos($node['base_expr'], "*") !== false && $node['sub_tree'] === false) {
                //we have found an all operator and need to find the corresponding
                //table to look things up

                $tableFullName = false;

                $dbName = $this->_extractDbName($node);
                $tableName = $this->_extractTableName($node);
                $colName = $this->_extractColumnName($node);

                if($dbName !== false) {
                    $tableFullName = "`" . $dbName . "`.`" . $tableName . "`";
                } else if ($tableName !== false) {
                    $tableFullName = "`" . $tableName . "`";
                }

                $table = array();
                $alias = array();
                if($tableFullName === false) {
                    //add everything *ed from all tables to this query
                    foreach($sqlTree['FROM'] as $fromNode) {
                        if($this->_isTable($fromNode)) {
                            $table[] = $fromNode['table'];
                            if(!$this->_hasAlias($fromNode)) {
                                $alias[] = $fromNode['table'];
                            } else {
                                $alias[] = $fromNode['alias']['name'];
                            }
                        } else if ($this->_isSubquery($fromNode)) {
                            //handle subqueries...
                            $this->_parseSqlAll_linkSubquerySELECT($fromNode['sub_tree'], $sqlTree, $fromNode['alias']['name']);
                        }
                    }
                } else {
                    foreach($sqlTree['FROM'] as $fromNode) {
                        //it could be, that the table here is actually another aliased table (which should
                        //have been processed here already, since SELECT is called last) -> link to tree
                        if($this->_isTable($fromNode)) {
                            if($this->_hasAlias($fromNode)) {
                                if(trim($fromNode['alias']['name'], "`") === $tableName) {
                                    $table[] = $fromNode['table'];
                                    break;
                                }
                            } else {
                                if($fromNode['table'] === $tableFullName) {
                                    $table[] = $fromNode['table'];
                                    break;
                                }
                            }
                        } else if ($this->_isSubquery($fromNode)) {
                            if(trim($fromNode['alias']['name'], "`") === $tableName) {
                                $this->_parseSqlAll_linkSubquerySELECT($fromNode['sub_tree'], $sqlTree, $tableName);
                                continue 2;
                            }           
                        }
                    }
                    $alias[] = $tableFullName;
                }
                
                if(empty($table))
                    continue;

                //now that we know the table, we need to look up what is in there
                foreach(array_keys($table) as $key) {
                    $this->_parseSqlAll_getColsDaiquiri($sqlTree, $node, false, $table[$key], $alias[$key]);
                }
            } else {
                array_push($sqlTree['SELECT'], $node);
            }
        }
    }

    function _parseSqlAll_linkSubquerySELECT(&$subtreeNode, &$resultTree, $alias) {
        //link the rows to the tree
        $count = 0;
        foreach($subtreeNode['SELECT'] as $selNode) {
            if($this->_isReserved($selNode)) {
                array_push($resultTree['SELECT'], $selNode);
                continue;
            }

            $tmp = $this->_parseSqlAll_parseResourceName($selNode['base_expr']);

            unset($tmp[0]);
            $count = 0;
            $selNode['alias'] = array("as" => true,
                                      "name" => "",
                                      "base_expr" => "as ");
            foreach($tmp as $element) {
                $selNode['no_quotes'] = array("delim" => ".", "parts" => array());
                $selNode['alias']['no_quotes'] = array("delim" => ".", "parts" => array());

                if($count === 0) {
                    $selNode['base_expr'] = "`" . $alias . "`.`" . $element;
                    $selNode['no_quotes']['parts'] = array($alias, trim($element, "`"));
                    $selNode['alias']['name'] = "`" . $alias . "__" . $element;
                    $selNode['alias']['no_quotes']['parts'] = array($alias . "__" . trim($element, "`"));
                } else {
                    $selNode['base_expr'] .= "__" . $element;
                    $selNode['alias']['name'] .= "__" . $element;
                }

                $count += 1;
            }

            if(empty($selNode['no_quotes']['parts'])) {
                $selNode['no_quotes']['parts'][] = $selNode['base_expr'];
                $selNode['alias']['no_quotes']['parts']['parts'][] = $selNode['alias']['name'];
            }

            $selNode['base_expr'] .= "`";
            $selNode['alias']['name'] .= "`";
            $selNode['alias']['base_expr'] .= $selNode['alias']['name'];

            if($count === 0) {
                $node = $selNode;
            } else {
                array_push($resultTree['SELECT'], $selNode);
            }

            $count++;
        }
    }

    function _parseSqlAll_parseResourceName($resource) {
        $tmp = array();
        $tmp[0] = $resource;

        $split = explode(".", $tmp[0]);
        $currFullName = "";
        foreach($split as $token) {
            $numQuote = substr_count($token, "`");
            if($numQuote === 2 || ($currFullName === "" && $numQuote === 0)) {
                //either `foo` or foo. token
                $tmp[] = trim($token, "`");
            } else if ($currFullName !== "" && $numQuote === 1) {
                $currFullName .= "." . trim($token, "`");
                $tmp[] = $currFullName;
                $currFullName = "";
            } else {
                if($currFullName === "") {
                    $currFullName .= trim($token, "`");
                } else {
                    $currFullName .= "." . trim($token, "`");
                }
            }
        }

        return $tmp;
    }

    function _parseSqlAll_getColsDaiquiri(&$sqlTree, &$node, $zendAdapter, $table, $alias) {
        //Zend_Debug::dump($table); die(0);
        $resParts = $this->_parseSqlAll_parseResourceName($table);
        
        //process the alias name
        $aliasParts = $this->_parseSqlAll_parseResourceName($alias);
        unset($aliasParts[0]);

        $aliasName = "";
        foreach($aliasParts as $part) {
            if($aliasName === "") {
                $aliasName .= "`" . $part . "`";
            } else {
                $aliasName .= ".`" . $part . "`";
            }
        }

        //check if the given table resource is composed of DATABASE.TABLE
        if(count($resParts) !== 3) {
             throw new Exception("Cannot resolve table columns, table name is not valid.");
        }

        $tableResource = new Data_Model_Resource_Tables();

        $tableData = $tableResource->fetchRowByName($resParts[1], $resParts[2], true);

        if($tableData === false) {
            throw new Exception("Table {$table} does not exist.");
        }

        foreach($tableData['columns'] as $count => $row) {
            if($count == 0) {
                //this is the item we change
                if($alias === false || empty($alias)) {
                    $node['base_expr'] = "`" . $row['name'] . "`";
                    $node['no_quotes'] = array("delim" => ".", "parts" => array($row['name']));
                } else {
                    $node['base_expr'] = $aliasName . ".`" . $row['name'] . "`";
                    $node['no_quotes'] = array("delim" => ".", "parts" => array_merge($aliasParts, array($row['name'])));
                    $node['alias'] = array("as" => true,
                                       "name" => "`" . str_replace(".", "__", str_replace("`", "", $node['base_expr'])) . "`",
                                       "base_expr" => "as `" . str_replace(".", "__", str_replace("`", "", $node['base_expr'])) . "`",
                                       "no_quotes" => array("delim" => ".", "parts" => array(str_replace(".", "__", str_replace("`", "", $node['base_expr'])))));
                }
                $node['delim'] = ",";
                $nodeTemplate = $node;

                array_push($sqlTree['SELECT'], $node);
            } else {
                $newNode = $nodeTemplate;           //this is set on the first passing when count is 0
                if($alias === false || empty($alias)) {
                    $newNode['base_expr'] = "`" . $row['name'] . "`";
                    $newNode['no_quotes'] = array("delim" => ".", "parts" => array($row['name']));
                } else {
                    $newNode['base_expr'] = $aliasName . ".`" . $row['name'] . "`";
                    $newNode['no_quotes'] = array("delim" => ".", "parts" => array_merge($aliasParts, array($row['name'])));
                    $newNode['alias'] = array("as" => true,
                                       "name" => "`" . str_replace(".", "__", str_replace("`", "", $newNode['base_expr'])) . "`",
                                       "base_expr" => "as `" . str_replace(".", "__", str_replace("`", "", $newNode['base_expr'])) . "`",
                                       "no_quotes" => array("delim" => ".", "parts" => array(str_replace(".", "__", str_replace("`", "", $newNode['base_expr'])))));
                }
                
                array_push($sqlTree['SELECT'], $newNode);
            }
        }
    }

    function _isSubquery($node) {
        if(isset($node['expr_type']) && $node['expr_type'] === 'subquery') {
            return true;
        } else {
            return false;
        }
    }

    function _isTable($node) {
        if(isset($node['expr_type']) && $node['expr_type'] === 'table') {
            return true;
        } else {
            return false;
        }
    }

    function _isColref($node) {
        if(isset($node['expr_type']) && $node['expr_type'] === 'colref') {
            return true;
        } else {
            return false;
        }
    }

    function _isReserved($node) {
        if(isset($node['expr_type']) && $node['expr_type'] === 'reserved') {
            return true;
        } else {
            return false;
        }
    }

    function _isExpression($node) {
        if(isset($node['expr_type']) && $node['expr_type'] === 'expression') {
            return true;
        } else {
            return false;
        }
    }

    function _hasAlias($node) {
        if(isset($node['alias']) && $node['alias'] !== false) {
            return true;
        } else {
            return false;
        }
    }

    function _extractDbName($node) {
        //is this a table type or something else
        if($this->_isTable($node)) {
            $partCounts = count($node['no_quotes']['parts']);

            //a table node
            if($partCounts > 1) {
                return $node['no_quotes']['parts'][ $partCounts - 2 ];
            } else {
                return false;
            }
        } else if($this->_isColref($node)) {
            //if this is a "*" node, as in SELECT * FROM, then the no_quotes part is not present
            //and it does not make sense to extract anything anyways
            if(!isset($node['no_quotes'])) {
                return false;
            }

            $partCounts = count($node['no_quotes']['parts']);

            if($partCounts > 2) {
                return $node['no_quotes']['parts'][ 0 ];
            } else {
                return false;
            }
        } else {
            //don't know what to do
            return false;
        }
    }

    function _extractTableName($node) {
        //is this a table type or colref/alias?
        if($this->_isTable($node)) {
            $partCounts = count($node['no_quotes']['parts']);
        
            //a table node
            return $node['no_quotes']['parts'][ $partCounts - 1 ];
        } else if ( $this->_isColref($node) || isset($node['as']) ) {

            //if this is a "*" node, as in SELECT * FROM, then the no_quotes part is not present
            //and it does not make sense to extract anything anyways
            if(!isset($node['no_quotes'])) {
                return false;
            }

            $partCounts = count($node['no_quotes']['parts']);

            if($partCounts > 1) {
                return $node['no_quotes']['parts'][ $partCounts - 2 ];
            } else {
                return false;
            }

        } else {
            //don't know what to do
            return false;
        }
    }

    function _extractColumnName($node) {
        //is this a table type or colref/alias?
        if ( $this->_isColref($node) || isset($node['as']) ) {

            //if this is a "*" node, as in SELECT * FROM, then the no_quotes part is not present
            //and it does not make sense to extract anything anyways
            if(!isset($node['no_quotes'])) {
                return false;
            }

            $partCounts = count($node['no_quotes']['parts']);

            return $node['no_quotes']['parts'][ $partCounts - 1 ];
        } else {
            //don't know what to do
            return false;
        }
    }
}
