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
    protected function _sqloptions(array $queryParams = array()) {
        // parse options
        $sqloptions = array();
        if (isset($queryParams['cols'])) {
            $sqloptions['from'] = $queryParams['cols'];
        } else {
            $sqloptions['from'] = null;
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
            $adapter = Zend_Db_Table::getDefaultAdapter();

            // get the full columns id
            $resource = $this->getResource();
            $dbCols = $resource->fetchDbCols();
            
            $cols = array();
            if (isset($queryParams['cols']) && $queryParams['cols'] !== null) {
                foreach ($queryParams['cols'] as $col) {
                    $cols[] = $dbCols[$col];
                }
            } else {
                $cols = $dbCols;
            }

            foreach ($cols as $col) {
                $quotedString = $adapter->quoteInto('?', $queryParams['search']);
                $string = substr($quotedString, 1, strlen($quotedString) - 2);
                $sqloptions['orWhere'][] = $col . " LIKE '%" . $string . "%'";
            }
        }

        return $sqloptions;
    }

    /**
     * @brief   Returns the table in a paginated way. Compatible with jqGrid.
     * @param   array $rows         array of rows to return (comming from SQL query???)
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
    protected function _response(array $rows, array $sqloptions) {
        // fill data object
        $data = array();

        // rows from the database query
        $data['rows'] = array();
        foreach ($rows as $row) {
            $data['rows'][] = array_values($row);
        }

        // number of rows
        $data['nrows'] = count($rows);
        $data['total'] = $this->getResource()->countRows($sqloptions);

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

        return $data;
    }

}