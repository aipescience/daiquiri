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

class Query_Model_Resource_Groups extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Query_Groups');
    }

    /**
     * Inserts a query job group and appends it to the end of the linked list
     * @param array $data row data
     * @return int
     * @throws Exception
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // set prev_id to last group
        $data['prev_id'] = $this->fetchLastId();

        // set next_id to NULL
        $data['next_id'] = NULL;

        // set hidden to false
        $data['hidden'] = false;

        // store the values in the database
        $this->getAdapter()->insert($this->getTablename(), $data);

        // get the id of the new row
        $id = $this->getAdapter()->lastInsertId();

        // store this group as next for previously last group
        $this->getAdapter()->update('Query_Groups', array('next_id' => $id), array('`id` = ?' => $data['prev_id']));

        return $id;
    }

    /**
     * Moves a query job group to a new position in the linked list.
     * @param int   $id        primary key of the row
     * @param int   $prevId    primary key of the previous row
     * @param int   $nextId    primary key of the next row
     * @param int   $newPrevId primary key of the new previous row
     * @param array $errors    array for the errors
     */
    public function moveRow($id, $prevId, $nextId, $newPrevId, &$errors) {
        // check the provided prev_id
        if ($newPrevId === $id) {
            // set error message
            $errors = array('Can not asign the id of the current group as previous group');

        } else if ($newPrevId === $prevId) {
            // noting changed, do nothing

        } else if (empty($newPrevId)) {
            // it is the new first group

            // update previous and next rows at the old position
            $this->getAdapter()->update('Query_Groups', array('next_id' => $nextId), array('id = ?' => $prevId));
            $this->getAdapter()->update('Query_Groups', array('prev_id' => $prevId), array('id = ?' => $nextId));

            // update the previously first group
            $oldFirstId = $this->fetchFirstId();
            $this->getAdapter()->update('Query_Groups', array('prev_id' => $id), array('id = ?' => $oldFirstId));

            // update current row
            $this->getAdapter()->update('Query_Groups', array('prev_id' => NULL, 'next_id' => $oldFirstId), array('id = ?' => $id));

        } else {
            // fetch the new previous group
            $prev = $this->fetchRow($newPrevId);
            if ($prev === false) {
                // set error message
                $errors = array('Group not found');
            } else {
                // update the old previous and next group
                $this->getAdapter()->update('Query_Groups', array('next_id' => $nextId), array('id = ?' => $prevId));
                $this->getAdapter()->update('Query_Groups', array('prev_id' => $prevId), array('id = ?' => $nextId));

                // update the new previous and next group
                $this->getAdapter()->update('Query_Groups', array('next_id' => $id), array('id = ?' => $prev['id']));
                $this->getAdapter()->update('Query_Groups', array('prev_id' => $id), array('id = ?' => $prev['next_id']));

                // update current row
                $this->getAdapter()->update('Query_Groups', array('prev_id' => $prev['id'], 'next_id' => $prev['next_id']), array('id = ?' => $id));
            }
        }
    }

    /**
     * Deletes a query job group and removes the foreign keys of the jobs in this group.
     * @param int $id primary key of the row
     * @throws Exception
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // get row
        $row = $this->fetchRow($id);

        // update previous and next rows
        $this->getAdapter()->update('Query_Groups', array('next_id' => $row['next_id']), array('id = ?' => $row['prev_id']));
        $this->getAdapter()->update('Query_Groups', array('prev_id' => $row['prev_id']), array('id = ?' => $row['next_id']));

        // remove the foreign keys of the affected jobs
        $this->getAdapter()->update('Query_Jobs', array('group_id' => null), array('group_id = ?' => $id));

        // remove the group
        $this->getAdapter()->delete($this->getTablename(), array('id = ?' => $id));
    }

    /**
     * Returns the id of the first group in the linked list
     * @return int $id primary key of the first group
     */
    public function fetchFirstId() {
        $select = $this->select(array(
            'where' => array(
                'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                'prev_id IS NULL'
            )
        ));
        $select->from('Query_Groups', array('id'));

        $row = $this->fetchOne($select);
        if ($row === false) {
            return NULL;
        } else {
            return $row['id'];
        }
    }

    /**
     * Returns the id of the last group in the linked list
     * @return int $id primary key of the last group
     */
    public function fetchLastId() {
        $select = $this->select(array(
            'where' => array(
                'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                'next_id IS NULL'
            )
        ));
        $select->from('Query_Groups', array('id'));

        $row = $this->fetchOne($select);
        if ($row === false) {
            return NULL;
        } else {
            return $row['id'];
        }
    }
}