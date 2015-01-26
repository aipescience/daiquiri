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
 * @class   Daiquiri_View_Helper_Cols Cols.php
 * @brief   Daiquiri View helper for displaying key value paired information
 * 
 * Class implementing a Zend view helper for displaying key value paired information
 * as a table where the keys are output in bold face and the value besides it as columns:
 * *key*: value 
 * 
 */
class Daiquiri_View_Helper_Cols extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   cols method - produces a table with key value pairs for given data
     * @param   array $data: array of data with subarrays containing the key name (as
     *                       array key) and the value
     * @return  HTML table with data
     * 
     * Produces a table from an array containing data rows with subarrays containing the
     * data as array key and value (key - value pairs). Each key-value pair represents one
     * column in the table. 
     * 
     */
    public function cols($cols) {
        $s = '';

        // loop over cols
        foreach ($cols as $col) {
            $s .= '<table class="table table-bordered">';

            // loop over keys in col
            foreach ($col as $key => $value) {
                $s .= '<tr><td class="onehundredtwenty">' . $this->view->escape($key) . '</td>';
                if (is_array($value)) {
                    $s .= '<td>' . $this->view->escape(implode(', ', $value)) . '</td></tr>';
                } else {
                    $s .= '<td>' . $this->view->escape($value) . '</td></tr>';
                }
            }

            $s .= '</table>';
        }

        return $s;
    }

}
