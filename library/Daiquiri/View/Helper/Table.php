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
 * @class   Daiquiri_View_Helper_Table Table.php
 * @brief   Daiquiri View helper for displaying tabular data
 * 
 * Creates the HTML table for given data. Data needs to supply column headers
 * separate from the actual data. Everything is passed as arrays.
 * 
 */
class Daiquiri_View_Helper_Table extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   table method - returns the HTML table for given data set
     * @param   array $cols: name of the columns contained in the header.
     * @param   array $data: array with the actual data (contained in a row parameter i,e, $data->rows)
     * @return  HTML with table
     * 
     * Produces a HTML table for the given columns and the given data. Data needs to be contained in an
     * object that stores the data in the "rows" parameter (WHY?). This is usually produced by Daiquiri
     * modules using the model->rows method.
     */
    public function table($cols, $rows) {
        $s = '<table class="table table-bordered">';

        // construct thead
        if (!empty($cols)) {
            $s .= "<thead><tr>";
            foreach ($cols as $col) {
                $s .= "<th>" . ucfirst($this->view->escape($col)) . "</th>";
            }
            $s .= "</tr></thead>";
        }

        // construct tbody
        $s .= "<tbody>";
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $s .= "<tr>";
                foreach ($row as $value) {
                    $s .= "<td>" . $this->view->escape($value) . "</td>";
                }
                $s .= "</tr>";
            }
            $s .= "</tbody>";
        }

        $s .= "</table>";
        return $s;
    }

}
