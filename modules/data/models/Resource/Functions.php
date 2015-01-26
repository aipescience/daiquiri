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

class Data_Model_Resource_Functions extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Data_Functions');
    }

    /**
     * Fetches all function entries.
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Data_Functions');
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchAll($select);
    }

    /**
     * Fetches one function entry specified by its id.
     * @param int $id id of the row
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`id` = ?", $id);
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchOne($select);
    }

    /**
     * Fetches the id of one function entry specified by the function name.
     * @param string $function name of function
     * @throws Exception
     * @return int $id
     */
    public function fetchRowByName($function) {
        if (empty($function)) {
            throw new Exception('$function not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`name` = ?", trim($function));
        $select->order('order ASC');
        $select->order('name ASC');

        return $this->fetchOne($select);
    }

    /**
     * Checks whether the user can access this function
     * @param int $function name of the function
     * @param int $role
     * @param string $command SQL command
     * @throws Exception
     * @return array
     */
    public function checkACL($function) {
        if (empty($function)) {
            throw new Exception('$function not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Data_Functions');
        $select->where("`name` = ?", trim($function));

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return false;
        } else {
            return Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']);
        }
    }

}
