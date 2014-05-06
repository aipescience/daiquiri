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

class Data_Model_Resource_Tables extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Tables');
    }

    /**
     * Fetches all table entries.
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Data_Tables');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchAll($select);
    }

    /**
     * Fetches the id of one table entry specified by the database and the table name.
     * @param string $db name of the database
     * @param string $table name of the table
     * @return int $id
     */
    public function fetchIdByName($db, $table) {
        if (empty($db) || empty($table)) {
            throw new Exception('$db or $table not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Tables');
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array());
        $select->where("`Data_Databases`.`name` = ?", trim($db));
        $select->where("`Data_Tables`.`name` = ?", trim($table));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return (int) $row['id'];
        }
    }

    /**
     * Fetches one table entry specified by its id.
     * @param int $id id of the row
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $columns = false) {
        if (empty($id)) {
            throw new Exception('$id or $table not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Tables');
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array(
            'database' => 'name', 'database_id' => 'id'
        ));
        $select->where("`Data_Tables`.`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        $row = $this->fetchOne($select);

        if ($columns === true) {
            $select = $this->select();
            $select->from('Data_Columns');
            $select->where('table_id = ?', $row['id']);
            $select->order('order ASC');
            $select->order('name ASC');

            $row['columns'] = $this->getAdapter()->fetchAll($select);
        }

        return $row;
    }

    /**
     * Fetches primary key and table entry (including database)
     * as a flat array.
     * @return array $rows
     */
    public function fetchValues($fieldname) {
        // get the name of the primary key
        $primary = $this->fetchPrimary();

        // get select object
        $select = $this->select();
        $select->from('Data_Tables', array('id', $fieldname));
        $select->join('Data_Databases','`Data_Databases`.`id` = `Data_Tables`.`database_id`', array(
            'database' => 'name'
        ));

        // query database, construct array, and return
        $rows = array();
        foreach($this->fetchAll($select) as $row) {
            $rows[$row['id']] = $row['database'] . '.' . $row['name'];
        }
        return $rows;
    }

    /**
     * Inserts one table entry and, optionally, fills the columns with information from 
     * the database or a provided array.
     * Returns the primary key of the new row.
     * @param array $data row data
     * @throws Exception
     * @return int $id
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if (isset($data['autofill'])) {
            $autofill = $data['autofill'];
            unset($data['autofill']);
        }

        if (isset($data['tableDescription'])) {
            $tableDescription = $data['tableDescription'];
            unset($data['tableDescription']);
        }

        // store row in database and get id
        $this->getAdapter()->insert('Data_Tables', $data);
        $id = $this->getAdapter()->lastInsertId();

        if (isset($autofill) && $autofill = true) {
            // get the additional resources
            $columnResource = new Data_Model_Resource_Columns();
            $databaseResource = new Data_Model_Resource_Databases();

            // auto create entries for all columns
            $row = $databaseResource->fetchRow($data['database_id']);
            $database = $row['name'];
            $table = $data['name'];

            try {
                if(empty($tableDescription)) {
                    $descResource = new Data_Model_Resource_Description();
                    $descResource->init($database); 
                    $tableDescription = $descResource->describeTable($table);
                }

                foreach ($tableDescription['columns'] as $column) {
                    $column['table'] = $table;
                    $column['table_id'] = $id;
                    $column['database'] = $database;

                    $columnResource->insertRow($column);
                }
            } catch (Exception $e) {
                $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $id));
                throw $e;
            }
        }

        return $id;
    }

    /**
     * Deletes a table entry and all its columns.
     * @param int $id id of the row
     * @throws Exception
     * @return type 
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $row = $this->fetchRow($id, true);

        // delete tables and columns of this database
        foreach ($row['columns'] as $column) {
            $this->getAdapter()->delete('Data_Columns', array('`id` = ?' => $column['id']));
        }
        $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $id));
    }

    /**
     * Checks whether the user can access this table
     * @param int $id id of the row
     * @param int $role
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
        if (empty($id) || empty($command)) {
            throw new Exception('$id or $command not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $row = $this->fetchRow($id, false);
        $command = strtolower($command);

        // check if the database is published for this role
        $result = Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);

        if (($command === "select" ||
                $command === "set" ) &&
                $row['publication_select'] === "1") {

            return $result;
        } else if (($command === "alter" ||
                $command === "update" ) &&
                $row['publication_update'] === "1") {

            return $result;
        } else if (($command === "create" ||
                $command === "drop" ||
                $command === "insert" ) &&
                $row['publication_insert'] === "1") {

            return $result;
        } else {
            return false;
        }
    }

}
