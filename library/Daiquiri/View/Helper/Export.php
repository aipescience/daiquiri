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

class Daiquiri_View_Helper_Export extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function export($data, $indent = 4) {
        $this->_buildExport_r($data, $indent, $output);
        return $output;
    }

    private function _buildExport_r($data, $indent, &$output) {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $output .= str_repeat(' ',$indent) . "array(\n";
                } else {
                    $output .= str_repeat(' ',$indent) . "'{$key}' => array(\n";
                }

                $this->_buildExport_r($value,$indent + 4,$output);
                $output .= str_repeat(' ',$indent) . "),\n";
            } else {
                if (is_int($key)) {
                    $output .= str_repeat(' ',$indent) . "'{$value}',\n";
                } else {
                    $output .= str_repeat(' ',$indent) . "'{$key}' => '{$value}',\n";
                }
            }
        }
    }
}
