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

class Data_Model_Resource_Description extends Daiquiri_Model_Resource_Table {

    /**
     * Database name for this resource. Set by init().
     * @var string $_db
     */
    private $_db = null;

    /**
     * Getter for $_db.
     * @return string $db
     */
    public function getDb() {
        if (empty($this->_db)){
            throw new Exception('$db not set in ' . get_class($this));
        } else {
            return $this->_db;
        }
    }

    /**
     * Sets the adapter of the resource retroactively.
     * @param string $db name of the database
     * @throws Exception
     */
    public function init($db) {
        if (empty($db)) {
            throw new Exception('$db not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // set adapter
        $this->setAdapter(Daiquiri_Config::getInstance()->getUserDbAdapter($db));

        // set database variable
        $this->_db = $db;
    }

    /**
     * Fetches the names of all tables in the set database.
     * @return array $tables
     */
    public function fetchTables() {
        return $this->getAdapter()->listTables();
    }

    /**
     * Fetches the description for a table from the database.
     * @param string $table name of the table
     * @throws Exception
     * @return array $tableDescription
     */
    public function describeTable($table) {
        if (empty($table)) {
            throw new Exception('$table not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // return empty if the table is locked
        $lockedTables = $this->getAdapter()->fetchAll('SHOW OPEN TABLES IN `' . $this->getDb() . '` WHERE In_use > 0');
        foreach ($lockedTables as $lockedTable) {
            if ($lockedTable['Table'] == $table) {
                return array();
            }
        }

        // prepare table description array
        $tableDescription = array(
            'name' => $table,
            'database' => $this->getDb(),
            'description' => '',
            'columns' => array()
        );

        // get the table description
        $cols = $this->getAdapter()->describeTable($table);

        // get all the comments for this table
        $comments = $this->fetchColumnComments($table);

        // assemble column information
        foreach ($cols as $col) {
            // get the right comment for this column
            $comment = $comments[$col['COLUMN_NAME']];

            // parse the comment section for old metadata information
            $metadata = $this->_getMetaFromComment($comment);

            $tableDescription['columns'][] = array(
                'name' => $col['COLUMN_NAME'],
                'order' => $col['COLUMN_POSITION'],
                'type' => $col['DATA_TYPE'],
                'unit' => $metadata['unit'],
                'ucd' => $metadata['ucd'],
                'description' => $metadata['description'],
                'comment' => $comment
            );
        }

        return $tableDescription;
    }

    /**
     * Fetches the comments for all columns of a certain table.
     * @param string $table name of the table
     * @throws Exception
     * @return array $comments
     */
    public function fetchColumnComments($table) {
        if (empty($table)) {
            throw new Exception('$table not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // this is DB specific, so handle differently
        $adapter = $this->getAdapter();
        $adapterClass = get_class($adapter);
        if ($adapterClass === "Zend_Db_Adapter_Pdo_Mysql") {

            //get information from information_schema
            $rows = $adapter->fetchAll("SELECT COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=" 
                . $adapter->quote($this->getDb()) . " AND TABLE_NAME=" 
                . $adapter->quote($table));

            $comments = array();
            foreach ($rows as $row) {
                $comments[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
            }
            return $comments;
        }

        return false;
    }

    /**
     * Fetches the comment for one columns of a certain table.
     * @param string $table name of the table
     * @param string $column name of the column
     * @throws Exception
     * @return array $comment
     */
    public function fetchColumnComment($table, $column) {
        if (empty($table) || empty($column)) {
            throw new Exception('$table or $column not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // this is DB specific, so handle differently
        $adapter = $this->getAdapter();
        $adapterClass = get_class($adapter);
        if ($adapterClass === "Zend_Db_Adapter_Pdo_Mysql") {

            //get information from information_schema
            $comment = $adapter->fetchOne("SELECT COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA="
                . $adapter->quote($this->getDb()) . " AND TABLE_NAME="
                . $adapter->quote($table) . " AND COLUMN_NAME="
                . $adapter->quote($column));

            return $comment;
        }

        return false;
    }

    /**
     * Stores the comment for a columns.
     * @param string $table name of the table
     * @param string $column name of the column
     * @param string $comment
     * @throws Exception
     * @return bool $success
     */
    public function storeColumnComment($table, $column, $comment) {
        if (empty($table) || empty($column) || empty($comment)) {
            throw new Exception('$table, $column or $comment not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // this is DB specific, so handle differently
        $adapter = $this->getAdapter();
        $adapterClass = get_class($adapter);
        if ($adapterClass === "Zend_Db_Adapter_Pdo_Mysql") {
            // get information from show create table, to properly construct alter table statement
            $adapter->setFetchMode(Zend_Db::FETCH_NUM);
            $createTable = $adapter->fetchRow("SHOW CREATE TABLE `" .
                trim($adapter->quote($this->getDb()), "'") . "`.`" .
                trim($adapter->quote($table), "'") . "`");

            $adapter->setFetchMode(Zend_Db::FETCH_ASSOC);

            // parse create table statement
            // assuming that create table statement always comes with \ns...
            $columnInfo = explode("\n", $createTable[1]);

            // throw away the first column, since that one is always the CREATE TABLE statement
            unset($columnInfo[0]);

            // find the column definition
            $definition = false;
            foreach ($columnInfo as $currColumn) {
                if (strpos($currColumn, "`{$column}`") !== false) {
                    $definition = trim($currColumn, "\r,\n ");
                    break;
                } else if (strpos($currColumn, " {$column} ") !== false) {
                    $definition = trim($currColumn, "\r,\n ");
                    break;
                }
            }

            if ($definition === false) {
                throw new Exception("Could not find column while setting comment.");
            }

            // remove any comment that might already be present
            $start = strpos($definition, "COMMENT ");
            // find first '
            $currPos = strpos($definition, "'", $start) + 1;
            if ($start !== false) {
                $end = false;
                while ($end === false) {
                    $end1 = strpos($definition, "'", $currPos);
                    $end2 = strpos($definition, "''", $currPos); //this is for handling strange cases in encoding

                    if ($end2 !== $end1) {
                        $end = $end1;
                        break;
                    } else {
                        $currPos = $end2 + 2;
                    }
                }

                $definition = substr($definition, 0, $start) . "COMMENT " . $adapter->quote($comment) . "" .
                        substr($definition, $end + 1);
            } else {
                $definition .= " COMMENT " . $adapter->quote($comment);
            }

            // building alter table statement
            $sql = "ALTER TABLE `" . trim($adapter->quote($this->getDb()), "'") . "`.`" .
                trim($adapter->quote($table), "'") . "` CHANGE `" .
                trim($adapter->quote($column), "'") . "` " . $definition;

            $result = $adapter->query($sql)->execute();

            return $result;
        }

        return false;
    }

    /**
     * Finds the end of a json sting.
     * @param string $string
     * @param string $offset
     * @return int $endPos
     */
    public function findJSONEnd($string, $offset = 0) {
        $count = 0;
        $endPos = false;

        while ($offset !== false) {
            $openParPos = strpos($string, "{", $offset);
            $closeParPos = strpos($string, "}", $offset);

            // handle error
            if ($openParPos === false && $closeParPos === false) {
                $offset = false;
                break;
            }

            // handle open parenthesis
            if ($openParPos !== false && $openParPos < $closeParPos) {
                $count += 1;
                $offset = $openParPos + 1;
                continue;
            }

            // handle closing parenthesis
            if ($openParPos > $closeParPos || ($openParPos === false && $closeParPos !== false)) {
                $count -= 1;
                $offset = $closeParPos + 1;

                if ($count === 0) {
                    $endPos = $closeParPos;
                    $offset = false;
                    break;
                }
            }
        }

        return $endPos;
    }

    /**
     * Extracts the metadata information from the comment string.
     * @param string $comment
     * @return array $metadata
     */
    private function _getMetaFromComment($comment) {
        $pos = strpos($comment, "DQIMETA=");
        if ($pos !== false) {
            $start = $pos + 8;
            $end = $this->findJSONEnd($comment, $pos);

            if ($end === false) {
                // something is wrong, so be on the save side...
                return array("ucd" => "", "unit" => "", "description" => "");
            }

            $json = substr($comment, $start, $end - $start + 1);
            $metadata = Zend_Json::decode($json);

            $result = array();
            if (isset($metadata['ucd'])) {
                $result['ucd'] = $metadata['ucd'];
            } else {
                $result['ucd'] = "";
            }

            if (isset($metadata['unit'])) {
                $result['unit'] = $metadata['unit'];
            } else {
                $result['unit'] = "";
            }

            if (isset($metadata['description'])) {
                $result['description'] = $metadata['description'];
            } else {
                $result['description'] = "";
            }

            return $result;
        } else {
            return array("ucd" => "", "unit" => "", "description" => "");
        }
    }

}
