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

class Contact_Model_Resource_Messages extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets the name of the database table.
     */
    public function __construct() {
        $this->setTablename('Contact_Messages');
    }

    /**
     * Fetches a set of rows specified by SQL keywords from the message table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());
        $select->join('Contact_Categories','Contact_Categories.id = Contact_Messages.category_id','category');
        $select->join('Contact_Status','Contact_Status.id = Contact_Messages.status_id','status');

        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches one row specified by its primary key from the message table.
     * @param mixed $id primary key of the row
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        $select = $this->select();
        $select->from('Contact_Messages');
        $select->where('Contact_Messages.id = ?', $id);
        $select->join('Contact_Categories','Contact_Categories.id = Contact_Messages.category_id','category');
        $select->join('Contact_Status','Contact_Status.id = Contact_Messages.status_id','status');

        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception($id . ' not found in ' . get_class($this) . '::fetchRow()');
        }

        return $row;
    }

}