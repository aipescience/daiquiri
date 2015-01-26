<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Data_Model_Resource_Databases extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Databases');
    }

    /**
     * Fetches all database entries.
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Data_Databases');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchAll($select);
    }

    /**
     * Fetches one database entry specified by its id.
     * @param int $id id of the row
     * @param bool $tables fetch table information
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id, $tables = false, $columns = false) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Databases');
        $select->where("`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        $row = $this->fetchOne($select);

        if (empty($row)) {
            return false;
        } else {
            if ($tables === true) {
                $select = $this->select();
                $select->from('Data_Tables');
                $select->where('database_id = ?', $row['id']);
                $select->order('order ASC');
                $select->order('name ASC');

                $tables = $this->fetchAll($select);

                foreach ($tables as &$table) {
                    if ($columns === true) {
                        $select = $this->select();
                        $select->from('Data_Columns');
                        $select->where('table_id = ?', $table['id']);
                        $select->order('order ASC');
                        $select->order('name ASC');

                        $table['columns'] = $this->fetchAll($select);
                    }
                }

                $row['tables'] = $tables;
            }

            return $row;
        }
    }

    /**
     * Fetches one database entry specified by the database name.
     * @param string $db name of database
     * @param bool $tables fetch table information
     * @param bool $columns fetch colums information
     * @throws Exception
     * @return int $id
     */
    public function fetchRowByName($db, $tables = false, $columns = false) {
        if (empty($db)) {
            throw new Exception('$db not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Databases');
        $select->where("`name` = ?", trim($db));
        $select->order('order ASC');
        $select->order('name ASC');

        $row = $this->fetchOne($select);

        if (empty($row)) {
            return false;
        } else {
            if ($tables === true) {
                $select = $this->select();
                $select->from('Data_Tables');
                $select->where('database_id = ?', $row['id']);
                $select->order('order ASC');
                $select->order('name ASC');

                $tables = $this->fetchAll($select);

                foreach ($tables as &$table) {
                    if ($columns === true) {
                        $select = $this->select();
                        $select->from('Data_Columns');
                        $select->where('table_id = ?', $table['id']);
                        $select->order('order ASC');
                        $select->order('name ASC');

                        $table['columns'] = $this->fetchAll($select);
                    }
                }

                $row['tables'] = $tables;
            }

            return $row;
        }
    }

    /**
     * Inserts one database entry and, if set, the fills the columns and tables automatically.
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

        // store row in database and get id
        $this->getAdapter()->insert('Data_Databases', $data);
        $id = $this->getAdapter()->lastInsertId();

        if (isset($autofill) && !empty($autofill)) {
            // get the additional resources
            $descResource = new Data_Model_Resource_Description();
            $tableResource = new Data_Model_Resource_Tables();

            // auto create entries for all tables
            try {
                $descResource->init($data['name']);
                foreach ($descResource->fetchTables() as $table) {
                    $desc = $descResource->describeTable($table);
                    $tableData = array(
                        'database_id' => $id,
                        'name' => $desc['name'],
                        'description' => $desc['description'],
                        'publication_role_id' => $data['publication_role_id'],
                        'publication_select' => $data['publication_select'],
                        'publication_update' => $data['publication_update'],
                        'publication_insert' => $data['publication_insert'],
                        'autofill' => true,
                        'tableDescription' => $desc
                    );
                    $tableResource->insertRow($tableData);
                }
            } catch (Exception $e) {
                // delete database entry again
                $this->getAdapter()->delete('Data_Databases', array('`id` = ?' => $id));
                throw $e;
            }
        }

        return $id;
    }

    /**
     * Deletes a database entry and all its tables and columns.
     * @param int $id id of the row
     * @throws Exception
     * @return type 
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $row = $this->fetchRow($id, true, true);

        // delete tables and columns of this database
        foreach ($row['tables'] as $table) {
            foreach ($table['columns'] as $column) {
                $this->getAdapter()->delete('Data_Columns', array('`id` = ?' => $column['id']));
            }
            $this->getAdapter()->delete('Data_Tables', array('`id` = ?' => $table['id']));
        }

        // delete database row
        $this->getAdapter()->delete('Data_Databases', array('`id` = ?' => $id));
    }

    /**
     * Checks whether the user can access this database
     * @param string $database name of the database
     * @param string $command SQL command
     * @return bool
     */
    public function checkACL($database, $command) {
        if (empty($database) || empty($command)) {
            throw new Exception('$database or $command not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Databases');
        $select->where("`name` = ?", trim($database));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        }

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
        } else if (($command === "show tables") &&
                $row['publication_show'] === "1") {

            return $result;
        } else {
            return false;
        }
    }
}
