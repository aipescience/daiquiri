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
    public function cols($data) {
        $s = '';

        // loop over cols
        foreach ($data as $col) {
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
