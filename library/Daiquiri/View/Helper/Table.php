<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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
                foreach ($row['cell'] as $value) {
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
