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
class Daiquiri_Model_Helper_Pagination extends Daiquiri_Model_Helper_Abstract {

    private $_model;

    public function __construct($model) {
        $this->_model = $model;
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

        if (isset($queryParams['sort']) && !empty($queryParams['sort'])) {
            $s = explode(' ', $queryParams['sort']);

            if (!isset($s[0]) || empty($s[0])) {
                throw new Exception('$sortField missing in ' . get_class($this) . '::' . __FUNCTION__);
            } else {
                $dbCols = $this->_model->getResource()->fetchCols();
                $sortField = $dbCols[$s[0]];

                // remove ticks
                $sortField = str_replace('`','',$sortField);
            }

            if (!isset($s[1]) || empty($s[1])) {
                throw new Exception('$sortOrder missing in ' . get_class($this) . '::' . __FUNCTION__);
            } else {
                $sortOrder = strtoupper($s[1]);
            }

            if (in_array($sortOrder, array('ASC', 'DESC'))) {
                if (is_array($sortField)) {
                    $sqloptions['order'] = array();
                    foreach ($sortField as $field) {
                        $sqloptions['order'][] = $field . ' ' . $sortOrder;
                    }
                } else {
                    $sqloptions['order'] = $sortField . ' ' . $sortOrder;
                }
            } else {
                throw new Exception('$sortOrder must be ASC or DESC in ' . get_class($this) . '::' . __FUNCTION__);
            }

        } else {
            $sqloptions['order'] = null;
        }

        $sqloptions['orWhere'] = array();
        if (isset($queryParams['search']) && !empty($queryParams['search'])) {
            $dbCols = $this->_model->getResource()->fetchCols();
            $modelCols = $this->_model->getCols();

            if (empty($modelCols)) {
                $modelCols = array_keys($dbCols);
            }

            // translate cols from the model to database columns
            $cols = array();
            foreach ($modelCols as $col) {
                if (isset($dbCols[$col])) {
                    if (is_array($dbCols[$col])) {
                        foreach ($dbCols[$col] as $col) {
                            $cols[] = $col;
                        }
                    } else {
                        $cols[] = $dbCols[$col];
                    }
                }
            }

            // add where statement for every column
            foreach ($cols as $col) {
                $quotedSearch = $this->_model->getResource()->getAdapter()->quoteInto('?', $queryParams['search']);
                $search = substr($quotedSearch, 1, strlen($quotedSearch) - 2);
                $sqloptions['orWhere'][] = $col . " LIKE '%" . $search . "%'";
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