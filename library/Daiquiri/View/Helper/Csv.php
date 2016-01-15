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

class Daiquiri_View_Helper_Csv extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function csv($cols, $rows, $mode = 'csv') {

        $output = '';

        $values = array();
        foreach ($cols as $col) {
            $values[] = "\"{$this->view->escape($col)}\"";
        }
        if ($mode == 'csv') {
            $output .= implode(',',$values) . "\n";
        } else {
            $output .= utf8_decode(implode(';',$values) . "\n");
        }

        foreach ($rows as $row) {
            $values = array();
            foreach ($row as $element) {
                $values[] = "\"{$this->view->escape($element)}\"";
            }

            if ($mode == 'csv') {
                $output .= implode(',',$values) . "\n";
            } else {
                $output .= utf8_decode(implode(';',$values)) . "\n";
            }
        }

        return $output;
    }
}
