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
 * @class   Daiquiri_Model_Pagination Pagination.php
 * @brief   Class, which adds pagination (for daiquiri_table.js) functionality to model.
 * 
 * This class provides, pagination of the SQL query results. It is used in connection 
 * with the daiquiri_table.js.
 * 
 * It further handles basic functionality of daiquiri_table.js such as sorting, filters and
 * editing of elements.
 */
class Daiquiri_Model_Pagination extends Daiquiri_Model_Abstract {

    private $_model;

    public function __construct($model) {
        $this->_model = $model;
    }

    public function rows($params) {
        // get the table from the resource
        $sqloptions = $this->sqloptions($params);
        $rows = $this->_model->getResource()->fetchRows($sqloptions);
        return $this->response($rows, $sqloptions);
    }

    /**
     * @brief   Maps options from daiquiri_table to SQL query options
     * @param   array $tableParams
     * @return  array $sqloptions
     * 
     * This function returns an array with the elements 'from', 'limit',
     * 'start', 'order', and 'where'. These map to the corresponding SQL
     * tags.
     */
    public function sqloptions(array $queryParams = array()) {
        // parse options
        $sqloptions = array();
        if (isset($queryParams['cols'])) {
            $sqloptions['from'] = $queryParams['cols'];
        } else {
            $sqloptions['from'] = array();
        }
        
        if (isset($queryParams['nrows'])) {
            $sqloptions['limit'] = $queryParams['nrows'];

            if (isset($queryParams['page'])) {
                $sqloptions['start'] = ($queryParams['page'] - 1) * $queryParams['nrows'];
            } else {
                $sqloptions['start'] = 0;
            }
        } else {
            $sqloptions['start'] = 0;
            $sqloptions['limit'] = null;
        }

        if (isset($queryParams['sort'])) {
            $s = explode(' ', $queryParams['sort']);

            if (count($s) == 2) {
                $sortField = $s[0];
                $sortOrder = strtoupper($s[1]);

                if (in_array($sortOrder, array('ASC', 'DESC'))) {
                    $sqloptions['order'] = $sortField . ' ' . $sortOrder;
                } else {
                    $sqloptions['order'] = null;
                }
            } else {
                $sqloptions['order'] = null;
            }
        } else {
            $sqloptions['order'] = null;
        }

        $sqloptions['orWhere'] = array();
        if (isset($queryParams['search']) && !empty($queryParams['search'])) {
            $cols = $this->_model->getCols();
            $adapter = $this->_model->getResource()->getAdapter();

            foreach ($cols as $col) {
                $quotedString = $adapter->quoteInto('?', $queryParams['search']);
                $string = substr($quotedString, 1, strlen($quotedString) - 2);
                $sqloptions['orWhere'][] = $adapter->quoteIdentifier($col) . " LIKE '%" . $string . "%'";
            }
        }
        return $sqloptions;
    }

    /**
     * @brief   Returns the table in a paginated way. Compatible with daiquiri_table.
     * @param   array $rows         array of rows to return
     * @param   array $sqloptions   sql options array encoding SQL filters
     * @return  data class
     * 
     * This function returns the data queried from the database in a form that
     * jqGrid can handle them. It returns a class with the following attributes:
     *  - <b>rows</b>       array holding the data ('id' and 'cell')
     *  - <b>page</b>       current page number
     *  - <b>total</b>      total number of pages
     *  - <b>records</b>    total number of rows
     */
    public function response(array $rows, array $sqloptions, $pk = false) {
        // fill data object
        $data = array();

        // rows from the database query
        $data['rows'] = array();
        foreach ($rows as $row) {
            $d = array("cell" => array_values($row));
            if ($pk !== false) {
                $d['id'] = $row[$pk];
            } 
            $data['rows'][] = $d;
        }

        // number of rows
        $data['nrows'] = count($rows);
        $data['total'] = $this->_model->getResource()->countRows($sqloptions);

        // finish response
        if (isset($sqloptions['start']) && isset($sqloptions['limit'])) {
            $data['page'] = $sqloptions['start'] / $sqloptions['limit'] + 1;
        } else {
            $data['page'] = 1;
        }

        if (isset($sqloptions['limit'])) {
            $data['pages'] = ceil($data['total'] / $sqloptions['limit']);
        } else {
            $data['pages'] = 1;
        }

        $data['status'] = 'ok'; 

        return $data;
    }

}