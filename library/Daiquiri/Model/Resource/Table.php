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

/**
 * Generic class for all resources which abstract one or more database tables
 */
class Daiquiri_Model_Resource_Table extends Daiquiri_Model_Resource_Abstract {

    /**
     * The list of database table classes the resource is connected to
     * @var array
     */
    protected $_tables = array();

    /**
     * Returns the first (or a specified) database table class connected to the model.
     * @param string $tableclass the name of the tableclass
     * @return Zend_Db_Table_Abstract
     */
    public function getTable($tableclass = null) {
        if ($tableclass) {
            return $this->_tables[$tableclass];
        } else {
            return reset($this->_tables);
        }
    }

    /**
     * Adds a database table class to the list of database tables
     * @param string $tableclass the name of the tableclass
     * @throws Exception
     */
    public function addTable($tableclass = null) {
        if ($tableclass) {
            $this->_tables[$tableclass] = new $tableclass();
        } else {
            throw new Exception('$tableclass not provided in ' . __METHOD__);
        }
    }

    /**
     * Adds an array of database table classes to the list of database tables.
     * @param array $tableclasses the names of the tableclasses
     * @throws Exception
     */
    public function addTables($tableclasses = array()) {
        if ($tableclasses) {
            foreach ($tableclasses as $tableclass) {
                $this->addTable($tableclass);
            }
        } else {
            throw new Exception('$tableclasses not provided in ' . __METHOD__);
        }
    }

    /**
     * Adds a database table class as the only entry in the list of 
     * database tables.
     * @param string $tableclass
     * @param string $schema schema/db name
     * @param string $name table name
     * @throws Exception
     */
    public function setTable($tableclass = null, $schema = null, $name = null) {
        // reset the tables array
        $this->_tables = array();

        // set table class
        if ($schema !== null) {
            if ($name !== null) {
                $this->_tables[$tableclass] = new $tableclass(array('name' => $name, 'schema' => $schema));
            } else {
                $this->_tables[$tableclass] = new $tableclass(array('schema' => $schema));
            }
        } else {
            $this->_tables[$tableclass] = new $tableclass();
        }
    }

    /**
     * Fetches the columns of the first (or a specified) database table.
     * @param string $tableclass the name of the tableclass
     * @return array
     */
    public function fetchCols($tableclass = null) {
        return $this->getTable($tableclass)->getCols();
    }

    /**
     * Fetches the colums as they are in the database.
     * @param string $tableclass the name of the tableclass
     * @return array
     */
    public function fetchDbCols($tableclass = null) {
        $cols = $this->fetchCols($tableclass);

        // get the name of the database table
        $t = $this->getTable()->getName();

        $dbCols = array();
        foreach ($cols as $col) {
            $dbCols[$col] = '`' . $t . '`.`' . $col . '`';
        }

        return $dbCols;
    }

    /**
     * Fetches one row specified by its id from the the first 
     * (or a specified) database table.
     * @param int $id
     * @param string $tableclass the name of the tableclass
     * @throws Exception
     * @return Zend_Db_Table_Row_Abstract
     */
    public function fetchRow($id, $tableclass = null) {
        $result = $this->getTable($tableclass)->find($id);

        if (count($result) === 0) {
            throw new Exception($id . ' not found in table ' . $tableclass . ' by ' . __METHOD__);
        }

        return $result->current()->toArray();
    }

    /**
     * Fetches a set of rows specified by SQL keywords from the first 
     * (or a specified) database table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @param string $tableclass the name of the tableclass
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchRows($sqloptions = array(), $tableclass = null) {
        // get the primary table
        $table = $this->getTable($tableclass);

        // get select object
        $select = $table->getSelect($sqloptions);

        // get result convert to array and return
        return $table->fetchAll($select)->toArray();
    }

    /**
     * Counts the number of rows in the first (or a specified) database table
     * specified database table. An optional WHERE clause can be provided.
     * @param string $where a where statement
     * @param string $tableclass the name of the tableclass
     * @throws Exception
     * @return int
     */
    public function countRows(array $sqloptions = null, $tableclass = null) {
        //get the table
        $table = $this->getTable($tableclass);

        // create selcet object
        $select = $table->select();
        $select->from($table, 'COUNT(*) as count');

        if ($sqloptions) {
            if (isset($sqloptions['where'])) {
                foreach ($sqloptions['where'] as $w) {
                    $select = $select->where($w);
                }
            }
            if (isset($sqloptions['orWhere'])) {
                foreach ($sqloptions['orWhere'] as $w) {
                    $select = $select->orWhere($w);
                }
            }
        }

        // query database and return
        return (int) $table->fetchRow($select)->count;
    }

    /**
     * Inserts a row in the the first (or a specified) database table according 
     * to the array $data.
     * @param array $data row data
     * @param string $tableclass the name of the tableclass
     * @return int $id primary key (id) of the inserted row
     * @throws Exception
     */
    public function insertRow($data = array(), $tableclass = null) {
        if ($data) {
            $table = $this->getTable($tableclass);
            $table->insert($data);
            return $table->getAdapter()->lastInsertId();
        } else {
            throw new Exception('$data not provided in ' . __CLASS__);
        }
    }

    /**
     * Updates a row specified by its id in the first (or a specified) database 
     * table according to the array $data.
     * @param int $id id of the row
     * @param array $data row data
     * @param string $tableclass the name of the tableclass
     * @throws Exception
     */
    public function updateRow($id = null, $data = null, $tableclass = null) {
        if ($id && $data) {
            // get the row by its primary key
            $row = $this->getTable($tableclass)->find($id)->current();

            if ($row) {
                // update row
                foreach ($this->fetchCols() as $col) {
                    if (array_key_exists($col, $data)) {
                        $row->$col = $data[$col];
                    }
                }

                // save row
                $row->save();
            } else {
                throw new Exception('user id "' . $id . '" not found in db in ' . __METHOD__);
            }
        } else {
            throw new Exception('$id or $data not provided in ' . __CLASS__);
        }
    }

    /**
     * Deletes a row specified by its id from the first (or a specified) 
     * database table.
     * @param int $id id of the row
     * @param string $tableclass the name of the tableclass
     * @throws Exception
     */
    public function deleteRow($id = null, $tableclass = null) {
        if ($id) {
            // get the row by its primary key
            $row = $this->getTable($tableclass)->find($id)->current();

            if (empty($row)) {
                throw new Exception('$id ' . $id . ' not found.');
            }

            // delete the row
            $row->delete();
        } else {
            throw new Exception('$id not provided in ' . __CLASS__);
        }
    }

}
