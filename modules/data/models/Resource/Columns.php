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

class Data_Model_Resource_Columns extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Columns');
    }

    /**
     * Fetches all column entries.
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Data_Columns');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchAll($select);
    }

    /**
     * Fetches the id of one column entry specified by the database, the table and the column name.
     * @param string $db name of the database
     * @param string $table name of the table
     * @param string $table name of the column
     * @throws Exception
     * @return int $id
     */
    public function fetchIdByName($db, $table, $column) {
        if (empty($db) || empty($table) || empty($column)) {
            throw new Exception('$db, $table or $column not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Columns');
        $select->join('Data_Tables','`Data_Tables`.`id` = `Data_Columns`.`table_id`', array());
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array());
        $select->where("`Data_Databases`.`name` = ?", trim($db));
        $select->where("`Data_Tables`.`name` = ?", trim($table));
        $select->where("`Data_Columns`.`name` = ?", trim($column));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return (int) $row['id'];
        }
    }

    /**
     * Fetches one column entry specified by its id.
     * @param int $id id of the row
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Columns');
        $select->join('Data_Tables','`Data_Tables`.`id` = `Data_Columns`.`table_id`', array(
            'table' => 'name', 'table_id' => 'id'
        ));
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array(
            'database' => 'name', 'database_id' => 'id'
        ));
        $select->where("`Data_Columns`.`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchOne($select);
    }

    /**
     * Inserts one column entry. Returns the primary key of the new row.
     * @param array $data row data
     * @throws Exception
     * @return int $id
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if (array_key_exists('comment', $data)) {
            $comment = $data['comment'];
            unset($data['comment']);
        }
        if (array_key_exists('database', $data)) {
            $database = $data['database'];
            unset($data['database']);
        }

        if (array_key_exists('table', $data)) {
            $table = $data['table'];
            unset($data['table']);
        }

        // store the values in the database
        $this->getAdapter()->insert('Data_Columns', $data);
        $id = $this->getAdapter()->lastInsertId();

        if (Daiquiri_Config::getInstance()->data->writeToDB) {
            // get information about the table from the the input or the table resource
            if (isset($database) && isset($table)) {
                $tableData = array(
                    'database' => $database,
                    'name' => $table
                );
            } else {
                $tableResource = new Data_Model_Resource_Tables();
                $tableData = $tableResource->fetchRow($data['table_id']);
            }

            unset($data['table_id']);

            if (isset($comment)) {
                $this->_writeColumnComment($tableData['database'], $tableData['name'], $data['name'], $data, $comment);
            } else {
                $this->_writeColumnComment($tableData['database'], $tableData['name'], $data['name'], $data);
            }
        }
    }

    /**
     * Updates a row specified by its primary key in the previously set database table.
     * according to the array $data.
     * @param int $id primary key of the row
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, $data) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $database = $data['database'];
        unset($data['database']);

        $table = $data['table'];
        unset($data['table']);

        $this->getAdapter()->update('Data_Columns', $data, array('`id`= ?' => $id));

        if (Daiquiri_Config::getInstance()->data->writeToDB) {
            unset($data['table_id']);
            $this->_writeColumnComment($database, $table, $data['name'], $data);
        }

    }
    
    /**
     * Writes the metadata for a column into the column comment in the database table. 
     * @param string $database
     * @param string $table
     * @param string $column
     * @param array $data metadata to be stored
     * @param string $oldComment existing comment string (from table description)
     * @throws Exception
     */
    private function _writeColumnComment($database, $table, $column, $data, $oldComment = false) {
        //check sanity of input
        foreach ($data as $key => $value) {
            if(is_string($data) && (strpos($data, "{") !== false || strpos($data, "}") !== false)) {
                throw new Exception("Unsupported character {} in " . $key . ": " . $data);
            }
        }

        // write metadata into comment field of the column (if supported)
        $descResource = new Data_Model_Resource_Description();
        $descResource->init($database);

        if ($oldComment === false) {
            $comment = $descResource->fetchColumnComment($table, $column);
            $oldComment = $comment;
        } else {
            $comment = $oldComment;
        }

        $json = Zend_Json::encode($data);

        // check if there is already a comment present with our metadata
        $charPos = strpos($comment, "DQIMETA=");

        if ($charPos !== false) {
            // find end of json
            $endPos = $descResource->findJSONEnd($comment, $charPos);

            if ($endPos === false) {
                throw new Exception("Cannot update MySQL meta data due to corruped column comment.");
            }

            $comment = substr($comment, 0, $charPos) . "DQIMETA=" . $json . substr($comment, $endPos + 1);
        } else {
            if (strlen($comment) > 0) {
                $comment .= ", DQIMETA=" . $json;
            } else {
                $comment = "DQIMETA=" . $json;
            }
        }

        // only do something if there is a change...
        if ($comment !== $oldComment) {
            $descResource->storeColumnComment($table, $column, $comment);
        }

        return true;
    }
}
