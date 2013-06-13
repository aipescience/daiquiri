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
 * @class   Daiquiri_Model_PaginatedTable PaginatedTable.php
 * @brief   Abstract base class for all Table models, which should display 
 *          tables compatible with jqGrid.
 * 
 * This class provides an abstract table model, that supports pagination of the
 * SQL query results. It is used in connection with the jqGrid.
 * 
 * It further handles basic functionality of jqGrids such as sorting, filters and
 * editing of elements.
 */
abstract class Daiquiri_Model_PaginatedTable extends Daiquiri_Model_Abstract {

    /**
     * @breif   Maps options from jqGrids to SQL query options
     * @param   array $tableParams
     * @return  array $sqloptions
     * 
     * This function returns an array with the elements 'from', 'limit',
     * 'start', 'order', and 'where'. These map to the corresponding SQL
     * tags.
     */
    protected function _sqloptions(array $tableParams = array()) {
        // parse options
        $sqloptions = array();
        if (isset($tableParams['cols'])) {
            $sqloptions['from'] = $tableParams['cols'];
        } else {
            $sqloptions['from'] = null;
        }
        if (isset($tableParams['rows'])) {
            $sqloptions['limit'] = $tableParams['rows'];
        } else {
            $sqloptions['limit'] = null;
        }
        if (isset($tableParams['rows']) && isset($tableParams['page'])) {
            $sqloptions['start'] = ($tableParams['page'] - 1) * $tableParams['rows'];
        } else {
            $sqloptions['start'] = 0;
        }
        if (isset($tableParams['sidx'])
                && isset($tableParams['sord'])
                && $tableParams['sidx'] != ''
                && in_array(strtoupper($tableParams['sord']), array('ASC', 'DESC'))) {
            $sqloptions['order'] = $tableParams['sidx'] . ' ' . strtoupper($tableParams['sord']);
        } else {
            $sqloptions['order'] = null;
        }
        if (isset($tableParams['_search'])
                && $tableParams['_search'] !== 'false'
                && isset($tableParams['searchField'])
                && isset($tableParams['searchOper'])
                && isset($tableParams['searchString'])) {

            // escape table and field
            $adapter = Zend_Db_Table::getDefaultAdapter();
            $table = $adapter->quoteIdentifier($this->getResource()->getTable()->getName());
            $col = $adapter->quoteIdentifier($tableParams['searchField']);
            $field = $table . '.' . $col;

            $operations = array(
                'eq' => ' = ?',
                'ne' => ' != ?',
                'bw' => ' LIKE "?%"',
                'bn' => ' NOT LIKE "?%"',
                'ew' => ' LIKE "%?"',
                'en' => ' NOT LIKE "%?"',
                'cn' => ' LIKE "%?%"',
                'nc' => ' NOT LIKE "%?%"',
                'nu' => ' IS NULL',
                'nn' => ' IS NOT NULL',
                'in' => ' IN (?)',
                'ni' => ' NOT IN (?)'
            );

            if (array_key_exists($tableParams['searchOper'], $operations)) {

                if (in_array($tableParams['searchOper'], array('in', 'ni'))) {
                    $a = array();
                    foreach (explode(',', $tableParams['searchString']) as $s) {
                        $a[] = $adapter->quoteInto('?', $s);
                    }
                    $string = implode(',', $a);
                } else {
                    $string = $adapter->quoteInto('?', $tableParams['searchString']);
                }

                $oper = $operations[$tableParams['searchOper']];
                $where = $field . preg_replace('/\?/', $string, $oper);

                $sqloptions['where'] = array($where);
            }
        } else {
            $sqloptions['where'] = array();
        }

        return $sqloptions;
    }

    /**
     * @brief   Returns the table in a paginated way. Compatible with jqGrid.
     * @param   array $rows         array of rows to return (comming from SQL query???)
     * @param   array $sqloptions   sql options array encoding SQL filters
     * @param   string $pk          name of primary key column
     * @return  data class
     * 
     * This function returns the data queried from the database in a form that
     * jqGrid can handle them. It returns a class with the following attributes:
     *  - <b>rows</b>       array holding the data ('id' and 'cell')
     *  - <b>page</b>       current page number
     *  - <b>total</b>      total number of pages
     *  - <b>records</b>    total number of rows
     */
    protected function _response(array $rows, array $sqloptions, $pk = 'id') {
        // fill data object
        $data = new stdClass();
        $data->rows = array();
        foreach ($rows as $row) {
            $data->rows[] = array('id' => $row[$pk], 'cell' => array_values($row));
        }

        // get the number of rows
        $nrows = $this->getResource()->countRows($sqloptions['where'], null);

        // finish response
        if (isset($sqloptions['start']) && isset($sqloptions['limit'])) {
            $data->page = $sqloptions['start'] / $sqloptions['limit'] + 1;
        } else {
            $data->page = 1;
        }
        if (isset($sqloptions['limit'])) {
            $data->total = ceil($nrows / $sqloptions['limit']);
        } else {
            $data->total = 1;
        }
        $data->records = $nrows;

        return $data;
    }

    /**
     * @brief   Edits a field in the table. Compatible with jqGrid.
     * @param   array $post     array with the edit command
     * @param   array $columns  array with the names of the columns
     * 
     * Edits a field in the jqGrid table and saves changes in the data
     * base.
     * 
     * The $post array should contain the fields:
     *  - 'oper' encoding the edit operation: allowed values: 'add', 'edit', 'del'
     *  - 'id'   id of current data row
     *  - column data of the row with the columns defined in $columns as keys
     */
    protected function _edit(array $post, array $columns = array()) {
        if ($post['oper'] == 'add') {
            // obtain the values from the request params
            foreach ($columns as $col) {
                if ($post[$col]) {
                    $data[$col] = $post[$col];
                }
            }

            // add row to the table
            $this->getResource()->insertRow($data);
        } elseif ($post['oper'] == 'edit') {
            // obtain the values from the request params
            foreach ($columns as $col) {
                if ($post[$col]) {
                    $data[$col] = $post[$col];
                }
            }

            // add row to the table
            $this->getResource()->updateRow($post['id'], $data);
        } elseif ($post['oper'] == 'del') {
            // add row to the table
            $this->getResource()->deleteRow($post['id']);
        }
    }

}