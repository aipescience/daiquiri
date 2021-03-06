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

class Daiquiri_Db_Select extends Zend_Db_Select {

    public function __construct($adapter, $sqloptions) {
        parent::__construct($adapter);

        // set limit
        if (!empty($sqloptions['limit'])) {
            if (!empty($sqloptions['start'])) {
                $start = $sqloptions['start'];
            } else {
                $start = 0;
            }
            $this->limit($sqloptions['limit'], $start);
        }

        // set order
        if (!empty($sqloptions['order'])) {
            $this->order($sqloptions['order']);
        }

        // set or where statement
        $this->setWhere($sqloptions);

        // set where statement
        $this->setOrWhere($sqloptions);
    }

    public function setWhere($sqloptions) {
        if (!empty($sqloptions['where'])) {
            foreach ($sqloptions['where'] as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value)) {
                        $where = array();
                        foreach ($value as $k => $v) {
                            $where[] = $this->getAdapter()->quoteInto($key, $v);
                        }
                        $this->where(implode(' OR ', $where));
                    } else {
                        $this->where($key, $value);
                    }
                } else {
                    $this->where($value);
                }
            }
        }
    }

    public function setOrWhere($sqloptions) {
        if (!empty($sqloptions['orWhere'])) {
            foreach ($sqloptions['orWhere'] as $key => $value) {
                if (is_string($key)) {
                    $this->orWhere($key, $value);
                } else {
                    $this->orWhere($value);
                }
            }
        }
    }
}
