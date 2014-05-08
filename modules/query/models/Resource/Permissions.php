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

/*
  Test cases for validation:
  insert into `multidark_test`.`FOF` (bla1, bla2) values (foo, bar);
  drop table `multidark_test`.`FOF`;
  drop table `adrian_db`.`foo`;
  drop database `multidark_test`;
  update `multidark_test`.`FOF` set foo=bla1 where bla=bla;
  create table if not exists `adrian_db`.`bla` (foo int, bar float);
  create table if not exists `multidark_test`.`bla` (foo int, bar float);
  alter offline table 'adrian_db`.`bla` add column bla int;
 */

/**
 * Model for the checking ACLs in the query system.
 */
class Query_Model_Resource_Permissions extends Daiquiri_Model_Resource_Abstract {

    /**
     * Cache for table ACLs
     * @var array $_tableACLCache
     */
    private $_tableACLCache = array();

    /**
     * Validates the sql string.
     * @param array $sqlParseTrees array of SQL parse trees
     * @param array $multiLineUsedDBs array with used database for each multiline query
     * @param array &$erros output for errors
     * @return TRUE if ok, otherwise FALSE
     */
    public function check(&$sqlParseTrees, &$multiLineUsedDBs, array &$errors) {
        return $this->_aclSQLCommands($sqlParseTrees, $multiLineUsedDBs, $errors);
    }

    /**
     * Goes through the SQL parse tree and checks whether the user tries to use
     * a SQL command he should not.
     * @param array of PHPSQLParser objects $sqlParseTrees
     * @param array $multiLineUsedDBs array with used database for each multiline query
     * @param error array $error or array of NULLs if OK
     * @return TRUE if ok, FALSE if not
     */
    private function _aclSQLCommands(&$sqlParseTrees, &$multiLineUsedDBs, array &$error) {
        $auth = Daiquiri_Auth::getInstance();
        $sum = 0;

        foreach ($multiLineUsedDBs as $db) {
            if ($this->_checkTableDBACL("SELECT", $db, false, $auth, $error) !== true) {
                return false;
            }
        }

        foreach ($sqlParseTrees as $key => $currQuery) {
            if ($currQuery === false) {
                $error['aclError'] = "Error in line " . ($key + 1) . ": " . "Could not parse query. Are you sure this is SQL?";
                return false;
            }

            $errorStr = array();
            if ($this->_checkACLSQLCommands_r($currQuery, $auth, $errorStr, $multiLineUsedDBs[$key]) !== true) {
                $error['aclError'] = "Error in line " . ($key + 1) . ": " . $errorStr[0];
                return false;
            }
        }

        return true;
    }

    /**
     * Recursive check on one SQL parse node
     * @param array of PHPSQLParser objects $parseNode
     * @param array for returning errors $error
     * @param current DB defined by any USE SQL statement
     * @param current SQL tag that is beeing parsed
     * @return TRUE if no errors, FALSE if errors
     */
    private function _checkACLSQLCommands_r(array $parseNode, &$auth, array &$error, $currDB = false, $currTag = false) {
        //this holds the type of query (i.e. SELECT, DROP, ....) assuming that the first entry
        //in the parse tree is always the determining type - this information needs to be
        //safed for ACL checking in DB and table access
        $queryType = false;

        //checking this assumption:
        if ($currTag === false) {
            if (!array_key_exists("SELECT", $parseNode) &&
                    !array_key_exists("DROP", $parseNode) &&
                    !array_key_exists("ALTER", $parseNode) &&
                    !array_key_exists("INSERT", $parseNode) &&
                    !array_key_exists("USE", $parseNode) &&
                    !array_key_exists("SHOW", $parseNode) &&
                    !array_key_exists("CREATE", $parseNode) &&
                    !array_key_exists("RENAME", $parseNode) &&
                    !array_key_exists("TRUNCATE", $parseNode) &&
                    !array_key_exists("CREATE", $parseNode) &&
                    !array_key_exists("LOAD", $parseNode) &&
                    !array_key_exists("DO", $parseNode) &&
                    !array_key_exists("DELETE", $parseNode) &&
                    !array_key_exists("HANDLER", $parseNode) &&
                    !array_key_exists("REPLACE", $parseNode) &&
                    !array_key_exists("SET", $parseNode) &&
                    !array_key_exists("PREPARE", $parseNode) &&
                    !array_key_exists("EXECUTE", $parseNode) &&
                    !array_key_exists("EXPLAIN", $parseNode) &&
                    !array_key_exists('0', $parseNode)                  #this one is for special cases
            ) {
                $error[] = "error in SQL: You have a syntax error in the first SQL statement or in the first statements of your sub-queries.";

                return false;
            }
        }

        foreach ($parseNode as $key => $currNode) {

            // expr_type is the usual key, however in INSERT there exists the key table... therefore check
            // for both
            if (is_array($currNode) && (array_key_exists('expr_type', $currNode) ||
                    array_key_exists('table', $currNode))) {
                // this is not a SQL top statement node
                // only do some checking if this is a subquery
                if (array_key_exists('sub_tree', $currNode)) {
                    if (!empty($currNode['sub_tree'])) {
                        if ($this->_checkACLSQLCommands_r($currNode['sub_tree'], $auth, $error, $currDB) !== true) {
                            return false;
                        }
                    }
                }

                //if we are using functions here, check if they can be used
                if ($this->_checkFunctionACL($currNode, $currDB, $auth, $error) !== true) {
                    return false;
                }

                //check if this is a reference to a DB/table and check ACL
                if (array_key_exists('table', $currNode) || $currNode['expr_type'] == "table") {
                    $db = "";
                    $usrDBName = "";
                    $table = "";
                    $this->_parseDBTableName($currNode['table'], $db, $table, $currDB);

                    if ($db === false) {
                        $error[] = "error in SQL: Did not specify DB for table " . $table;
                        return false;
                    }

                    //checking access to database
                    if ($this->_checkTableDBACL($currTag, $db, $table, $auth, $error) !== true) {
                        return false;
                    }
                }
            } else if (is_array($currNode)) {
                //this is a top SQL statement node like SELECT or FROM
                //we exclude the VALUES statement here, since it only connects to the INSERT one
                if (strtolower($key) == "drop") {
                    if ($this->_handleDROPstatement($currNode, $auth, $error, $currDB) !== true) {
                        return false;
                    }
                } else if (strtolower($key) == "create" || strtolower($key) == "alter") {
                    //do this distinction here, since the SQL parser does not yet implement these keywords
                    //and we need to do something more "classy"
                    if ($this->_handleCREATEandALTERstatement($currNode, $auth, $error, $key, $currDB) !== true) {
                        return false;
                    }
                } else if (strtolower($key) == "show") {
                    //checking show access to table
                    if ($this->_handleSHOWstatement($currNode, $auth, $error, $currDB) !== true) {
                        return false;
                    }
                } else if (strtolower($key) == "use" ||
                        strtolower($key) == "from" ||
                        strtolower($key) == "where" ||
                        strtolower($key) == "group" ||
                        strtolower($key) == "having" ||
                        strtolower($key) == "order" ||
                        strtolower($key) == "options" ||
                        strtolower($key) == "limit") {
                    //default enabling these SQL commands

                    if ($this->_checkACLSQLCommands_r($currNode, $auth, $error, $currDB, $queryType) !== true) {
                        return false;
                    }

                    continue;
                } else {
                    if ($queryType === false) {
                        $queryType = $key;
                    }

                    if ($this->_checkACLSQLCommands_r($currNode, $auth, $error, $currDB, $queryType) !== true) {
                        return false;
                    }
                }
            } else {
                //this is an end leaf...
                continue;
            }
        }

        return true;
    }

    /**
     * Check ACL for UDFs in a query
     * @param one node in the parse tree
     * @param current database
     * @param array for returning errors $error
     * @return TRUE if ok, FALSE if not
     */
    private function _checkFunctionACL(&$currNode, $db, &$auth, array &$error) {
        if (array_key_exists('expr_type', $currNode) && $currNode['expr_type'] == "function") {
            //allow standard SQL functions as default
            switch (strtolower($currNode['base_expr'])) {
                case 'abs':
                case 'acos':
                case 'asin':
                case 'atan':
                case 'atan2':
                case 'ceil':
                case 'ceiling':
                case 'conv':
                case 'cos':
                case 'cot':
                case 'crc32':
                case 'degerees':
                case 'exp':
                case 'floor':
                case 'ln':
                case 'log10':
                case 'log2':
                case 'log':
                case 'mod':
                case 'pi':
                case 'pow':
                case 'power':
                case 'radians':
                case 'rand':
                case 'round':
                case 'sign':
                case 'sin':
                case 'std':
                case 'sqrt':
                case 'tan':
                case 'truncate':
                    return true;
                    break;
            }

            // check function ACL in metadata
            $functionModel = new Data_Model_Functions();
            $response = $functionModel->show(array('function' => $currNode['base_expr']));

            if ($response['status'] === 'ok' && array_key_exists("row", $response) && 
            			$functionModel->getResource()->checkACL($response['row']['id'])) {
                return true;
            } else {
                $error[] = "No permission to use function named " . $currNode['base_expr'];
                return false;
            }
        }

        return true;
    }

    /**
     * Handles checking of ACLs in the DROP SQL statement. We donot allow "DROP DATABASE" at all!
     * @param one node in the parse tree
     * @param authentication object for ACL checking
     * @param array for returning errors $error
     * @param current DB defined by any USE SQL statement
     * @return TRUE if ok, FALSE if not
     */
    private function _handleDROPstatement(&$currNode, &$auth, array &$error, $currDB = false) {
        $sum = 0;

        if (array_key_exists('object_list', $currNode)) {
            foreach ($currNode['object_list'] as $object) {
                $db = "";
                $usrDBName = "";
                $table = "";

                if ($object['expr_type'] == "table") {
                    $this->_parseDBTableName($object['table'], $db, $table, $currDB);

                    if ($this->_checkTableDBACL("drop", $db, $table, $auth, $error) !== true) {
                        return false;
                    }
                } else if ($object['expr_type'] == "database") {
                    $error[] = "No permission to use DROP DATABASE!";

                    return false;

                    //code that would allow check:
                    /* $this->_parseDBTableName($object['table'], $db, $table, $currDB);

                      $db = $table;
                      $table = false;

                      $sum += $this->_checkTableDBACL("drop", $db, $table, $auth, $error); */
                }
            }
        }

        return true;
    }

    /**
     * Handles checking of ACLs in the SHOW SQL statement. ACLs are defined not collectively
     * for show, but on a case by case statement. At the moment, only "show databases" and
     * "show tables" are supported!
     * @param one node in the parse tree
     * @param authentication object for ACL checking
     * @param array for returning errors $error
     * @param current DB defined by any USE SQL statement
     * @return TRUE if ok, FALSE if not
     */
    private function _handleSHOWstatement(&$currNode, &$auth, array &$error, $currDB = false) {
        $sum = 0;

        //determine show command type
        $command = $currNode[0] . " " . $currNode[1];
        $command = strtolower($command);

        if ($command !== "show tables" && $command !== "show databases") {
            $error[] = "No permission to use SHOW command named " . strtoupper($command);
            return false;
        }

        //checking show access to table
        if ($this->_checkTableDBACL($command, $currDB, false, $auth, $error) !== true) {
            return false;
        }

        return true;
    }

    /**
     * Handles checking of ACLs in the CREATE and ALTER SQL statement. This is
     * needed, since PHP_SQL_PARSE does not yet handle these cases and we restort
     * to simply look for the DB of the user DB. THIS IS A RESTRICTION OF THIS METHOD,
     * it only works for the user DB and any defined scratch databases!
     * @param one node in the parse tree
     * @param authentication object for ACL checking
     * @param array for returning errors $error
     * @param string with the current SQL tag (CREATE or ALTER)
     * @param current DB defined by any USE SQL statement
     * @return TRUE if ok, FALSE if not 
     */
    private function _handleCREATEandALTERstatement(&$currNode, &$auth, array &$error, $currTag, $currDB = false) {
        $userDBName = Daiquiri_Config::getInstance()->getUserDBName($auth->getCurrentUsername());

        //check if generally user is allowed to use CREATE or ALTER
        if (!($auth->checkDbTable($userDBName, "*", strtolower($currTag)))) {
            $error[] = "No permission to use " . $currTag;
            return false;
        }

        //looking for the table name in the create table statement
        $id = array_search("TABLE", $currNode);
        $dbString = "";
        foreach ($currNode as $key => $value) {
            if ($key > $id) {
                if (!empty($value)) {
                    $dbString .= $value;
                } else if (!empty($dbString)) {
                    $db = "";
                    $table = "";
                    $this->_parseDBTableName($dbString, $db, $table, $currDB);

                    if ($db !== false) {
                        //checking access to database
                        if ($this->_checkTableDBACL($currTag, $db, $table, $auth, $error) !== true) {
                            return false;
                        }
                    }

                    break;
                }
            }
        }

        //if user DB was already set through a use statement, grant access immediately
        if ($currDB === $userDBName) {
            return true;
        }

        foreach ($currNode as $strToken) {
            if (strpos($strToken, $userDBName) !== false) {
                return true;
            }
        }

        $scratchDB = Daiquiri_Config::getInstance()->query->scratchdb;

        if (!empty($scratchDB) && $currDB === $scratchDB) {
            return true;
        }

        //if we reach this place, name of user DB not found in the query and revoke access
        $error[] = "No permission to use " . $currTag . " on anything else than your user DB";
        return false;
    }

    /**
     * Parse database and table name from a table specifier
     * @param the string to parse the names from
     * @param return variable with the database name
     * @param return variable with the table name
     * @param current DB defined by any USE SQL statement
     */
    private function _parseDBTableName(&$string, &$db, &$table, $currDB = false) {
        preg_match("/`?([^`.]*)`?\.?(.*)/", $string, $tmp);

        if ($tmp[2] !== "") {
            $table = trim($tmp[2], "`");
            $db = $tmp[1];
        } else {
            $table = trim($tmp[1], "`");
            $db = $currDB;
        }
    }

    /**
     * Check ACL for the database and table
     * @param current SQL parser tag
     * @param database name to check ACL for
     * @param table name to check ACL for
     * @param auth object
     * @param return array for errors
     * @return TRUE if ok, FALSE if error
     */
    private function _checkTableDBACL($currTag, $db, $table, &$auth, array &$error) {
        if (isset($this->_tableACLCache[$db][$table]) && in_array($currTag, $this->_tableACLCache[$db][$table])) {
            return true;
        }

        $scratchDB = Daiquiri_Config::getInstance()->query->scratchdb;

        if (!empty($scratchDB) && $db === $scratchDB) {
            return true;
        }

        if ($table !== false) {
            if (!($auth->checkDbTable($db, $table, strtolower($currTag)))) {
                $error[] = "Table does not exist or you have no permission to access db '" . $db . "' table '" . $table . "' with " . strtoupper($currTag);
                return false;
            }
        } else {
            //this is for drop database and the like
            if (!($auth->checkDbTable($db, $table, strtolower($currTag)))) {
                $error[] = "No permission to solely access db " . $db . " with " . strtoupper($currTag);
                return false;
            }
        }

        $this->_tableACLCache[$db][$table][] = $currTag;

        return true;
    }

}
