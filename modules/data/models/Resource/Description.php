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

    public function fetchTables($db, $adapter = null) {
        if ($adapter === null) {
            $adapter = 'user';
        }

        $config = Daiquiri_Config::getInstance()->getDbAdapterConfig($adapter);
        $config['dbname'] = $db;
        return Zend_Db::factory($config['adapter'], $config)->listTables();
    }

    public function describeTable($db, $table, $adapter = null) {
        if ($adapter === null) {
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $currTable = $this->getTable();
        $currTable->setAdapter($adapter);
        $currTable->setDb($db);
        $currTable->setName($table);

        try {
            $currTable->setPrimary();
        } catch (Exception $e) {
            //table without primary key. dirty hack this thing to actually
            //work without one (assuming there is one)

            $cols = $currTable->getColsDirect();
            $currTable->setPrimary($cols[0]);
        }

        //check if this table is locked
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $db . '` WHERE In_use > 0')->fetchAll();

        foreach ($lockedTables as $table) {
            if ($table['Table'] == $table) {
                return array();
            } else {
                return array();
            }
        }

        $info = $currTable->info();

        //get all the comments for this table
        $comments = $this->getColumnComments($db, $table);

        //get comment data for each column
        foreach ($info['cols'] as $col) {
            $name = $info['metadata'][$col]['COLUMN_NAME'];
            $info['metadata'][$col]['COLUMN_COMMENT'] = $comments[$name];
        }

        $table = array(
            'name' => $table,
            'database' => $db,
            'description' => '',
            'columns' => array()
        );

        foreach ($info['cols'] as $col) {
            //parse the comment section for old metadata information
            $metadata = $this->_getMetaFromComment($info['metadata'][$col]['COLUMN_COMMENT']);

            $table['columns'][] = array(
                'name' => $info['metadata'][$col]['COLUMN_NAME'],
                'position' => $info['metadata'][$col]['COLUMN_POSITION'],
                'type' => $info['metadata'][$col]['DATA_TYPE'],
                'unit' => $metadata['unit'],
                'ucd' => $metadata['ucd'],
                'description' => $metadata['description']
            );
        }

        return $table;
    }

    public function fetchColumns($db, $table, $adapter = null) {
        if ($adapter === null) {
            $adapter = 'user';
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setDb($db);
        $this->getTable()->setName($table);

        try {
            $this->getTable()->setPrimary();
        } catch (Exception $e) {
            //table without primary key. dirty hack this thing to actually
            //work without one (assuming there is one)

            $cols = $this->getTable()->getColsDirect();
            $this->getTable()->setPrimary($cols[0]);
        }

        $info = $this->getTable()->info();

        return $info['cols'];
    }

    public function describeColumn($db, $table, $column, $adapter = null) {
        if ($adapter === null) {
            $adapter = 'user';
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setDb($db);
        $this->getTable()->setName($table);

        try {
            $this->getTable()->setPrimary();
        } catch (Exception $e) {
            //table without primary key. dirty hack this thing to actually
            //work without one (assuming there is one)

            $cols = $this->getTable()->getColsDirect();
            $this->getTable()->setPrimary($cols[0]);
        }

        $info = $this->getTable()->info();

        $info['metadata'][$column]['COLUMN_COMMENT'] = $this->getColumnComment($db, $table, $column);

        $metadata = $this->_getMetaFromComment($info['metadata'][$column]['COLUMN_COMMENT']);

        return array(
            'name' => $info['metadata'][$column]['COLUMN_NAME'],
            'database' => $db,
            'table' => $table,
            'position' => $info['metadata'][$column]['COLUMN_POSITION'],
            'type' => $info['metadata'][$column]['DATA_TYPE'],
            'unit' => $metadata['unit'],
            'ucd' => $metadata['ucd'],
            'description' => $metadata['description'],
        );
    }

    //returns false if nothing found, otherwise the comment
    public function getColumnComments($db, $table, $adapter = null) {
        if ($adapter === null) {
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setDb($db);
        $this->getTable()->setName($table);

        //this is DB specific, so handle differently
        if (get_class($adapter) === "Zend_Db_Adapter_Pdo_Mysql") {

            //get information from information_schema
            $rows = $adapter->fetchAll("SELECT COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA="
                    . $adapter->quote($db) . " AND TABLE_NAME="
                    . $adapter->quote($table));

            $result = array();

            foreach ($rows as $row) {
                $result[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
            }

            return $result;
        }

        return false;
    }

    //returns false if nothing found, otherwise the comment
    public function getColumnComment($db, $table, $column, $adapter = null) {
        if ($adapter === null) {
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setDb($db);
        $this->getTable()->setName($table);

        //this is DB specific, so handle differently
        if (get_class($adapter) === "Zend_Db_Adapter_Pdo_Mysql") {

            //get information from information_schema
            $result = $adapter->fetchOne("SELECT COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA="
                    . $adapter->quote($db) . " AND TABLE_NAME="
                    . $adapter->quote($table) . " AND COLUMN_NAME="
                    . $adapter->quote($column));

            return $result;
        }

        return false;
    }

    //returns false if nothing found, otherwise the comment
    public function setColumnComment($db, $table, $column, $comment, $adapter = null) {
        if ($adapter === null) {
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        }

        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setDb($db);
        $this->getTable()->setName($table);

        //this is DB specific, so handle differently
        if (get_class($adapter) === "Zend_Db_Adapter_Pdo_Mysql") {
            //get information from show create table, to properly construct alter table statement
            $adapter->setFetchMode(Zend_Db::FETCH_NUM);
            $createTable = $adapter->fetchRow("SHOW CREATE TABLE `" .
                    trim($adapter->quote($db), "'") . "`.`" .
                    trim($adapter->quote($table), "'") . "`");

            $adapter->setFetchMode(Zend_Db::FETCH_ASSOC);

            //parse create table statement
            //assuming that create table statement always comes with \ns...
            $columnInfo = explode("\n", $createTable[1]);

            //throw away the first column, since that one is always the CREATE TABLE statement
            unset($columnInfo[0]);

            //find the column definition
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

            //remove any comment that might already be present
            $start = strpos($definition, "COMMENT ");
            //find first '
            $currPos = strpos($definition, "'", $start) + 1;
            if ($start !== false) {
                $end = false;
                while ($end === false) {
                    $end1 = strpos($definition, "'", $currPos);
                    $end2 = strpos($definition, "''", $currPos);          //this is for handling strange cases in encoding

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

            //building alter table statement
            $sql = "ALTER TABLE `" . trim($adapter->quote($db), "'") . "`.`" .
                    trim($adapter->quote($table), "'") . "` CHANGE `" .
                    trim($adapter->quote($column), "'") . "` " . $definition;

            $result = $adapter->query($sql)->execute();

            return $result;
        }

        return false;
    }

    public function findJSONEnd($string, $offset = 0) {
        $count = 0;
        $endPos = false;

        while ($offset !== false) {
            $openParPos = strpos($string, "{", $offset);
            $closeParPos = strpos($string, "}", $offset);

            //handle error
            if ($openParPos === false && $closeParPos === false) {
                $offset = false;
                break;
            }

            //handle open parenthesis
            if ($openParPos !== false && $openParPos < $closeParPos) {
                $count += 1;
                $offset = $openParPos + 1;
                continue;
            }

            //handle closing parenthesis
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

    private function _getMetaFromComment($comment) {
        $pos = strpos($comment, "DQIMETA=");
        if ($pos !== false) {
            $start = $pos + 8;
            $end = $this->findJSONEnd($comment, $pos);

            if ($end === false) {
                //something is wrong, so be on the save side...
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
