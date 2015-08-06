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

class Query_Model_Resource_Jobs extends Daiquiri_Model_Resource_Table {

    /**
     * Array of possible query types.
     * @var array
     */
    protected static $_types = array(
        'web' => 1,
        'uws' => 2
    );

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Query_Jobs');
    }

    /**
     * Fetches a set of rows specified by SQL keywords from the jobs table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from($this->getTablename(), array('id','database','table','time','status_id','type_id','group_id','prev_id','next_id','complete'));

        // query database and return
        return $this->fetchAll($select);
    }

    /**
     * Fetches one row specified by its primary key or an array of sqloptions
     * from the jobs table.
     * @param mixed $input primary key of the row OR array of sqloptions
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($input) {
        if (empty($input)) {
            throw new Exception('$id or $sqloptions not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $fields = array('id','database','table','time','status_id','prev_status_id','type_id','group_id','prev_id','next_id','complete','removed','user_id','query','actualQuery','nrows','size','ip');

        if (is_array($input)) {
            $select = $this->select($input);
            $select->from($this->getTablename(), $fields);
        } else {
            $select = $this->select();
            $select->from($this->getTablename(), $fields);
            $identifier = $this->quoteIdentifier($this->fetchPrimary());
            $select->where($identifier . '= ?', $input);
        }

        // get the rows an chach that its one and only one
        return $this->fetchOne($select);
    }

    /**
     * Returns the number of rows and the size of a given user database.
     * @param int $userId id of the user
     * @return array $stats
     */
    public function fetchStats($userId) {
        $select = $this->select();
        $select->from($this->getTablename(), 'SUM(nrows) as nrows,SUM(size) as size');
        $select->where('user_id = ?', $userId);
        $row = $this->fetchOne($select);

        if ($row['nrows'] === NULL) $row['nrows'] = 0;
        if ($row['size'] === null) $row['size'] = 0;

        return $row;
    }

    /**
     * Returns the type_id for a given job type.
     * @param string $type
     * @return int $type_id
     */
    public function getTypeId($type) {
        $classname = get_class($this);
        if (isset($classname::$_types[$type])) {
            return $classname::$_types[$type];
        } else {
            return false;
        }
    }

    /**
     * Returns the job type for a given type_id.
     * @param int $typeId
     * @return string $type
     */
    public function getType($typeId) {
        $classname = get_class($this);
        return array_search($typeId, $classname::$_types);
    }

    /**
     * Inserts a new job appends it to the end of the linked list
     * @param array $data row data
     * @return int
     * @throws Exception
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        // set group_id to NULL
        $data['next_id'] = NULL;

        // set prev_id to last group
        $data['prev_id'] = $this->fetchLastId();

        // set next_id to NULL
        $data['next_id'] = NULL;

        // store the values in the database
        $this->getAdapter()->insert('Query_Jobs', $data);

        // get the id of the new row
        $id = $this->getAdapter()->lastInsertId();

        // store this group as next for previously last group
        $this->getAdapter()->update('Query_Jobs', array('next_id' => $id), array('`id` = ?' => $data['prev_id']));

        return $id;
    }

    /**
     * Moves a query job to a new position in the linked list and/or to a new group.
     * @param int   $id         primary key of the job
     * @param int   $prevId     primary key of the previous job
     * @param int   $nextId     primary key of the next job
     * @param int   $newPrevId  primary key of the new previous job
     * @param int   $groupId    primary key of the job's group
     * @param int   $newGroupId primary key of the job's new group
     * @param array $errors     array for the errors
     */
    public function moveRow($id, $prevId, $nextId, $newPrevId, $groupId, $newGroupId, &$errors) {

        // set proper NULL for $newGroupId
        if (empty($newGroupId)) $newGroupId = NULL;

        if ($newPrevId === $id) {
            // set error message
            $errors = array('Can not asign the id of the current job as previous job');

        } else if ($newGroupId === $groupId) {
            // the group has not changed

            if ($newPrevId === $prevId) {
                // nothing changed, do nothing

            } else if (empty($newPrevId)) {
                // it is the new first job

                // update previous and next rows at the old position
                $this->getAdapter()->update('Query_Jobs', array('next_id' => $nextId), array('id = ?' => $prevId));
                $this->getAdapter()->update('Query_Jobs', array('prev_id' => $prevId), array('id = ?' => $nextId));

                // update the previously first job
                $oldFirstId = $this->fetchFirstId($groupId);
                $this->getAdapter()->update('Query_Jobs', array('prev_id' => $id), array('id = ?' => $oldFirstId));

                // update current row
                $this->getAdapter()->update('Query_Jobs', array('prev_id' => NULL, 'next_id' => $oldFirstId), array('id = ?' => $id));
            } else {
                // a new previous job was set

                // fetch the new previous job
                $prev = $this->fetchRow(array(
                    'where' => array(
                        'id = ?' => $newPrevId,
                        'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                        'group_id = ?' => $groupId,
                        'removed = 0'
                    )
                ));

                if ($prev === false) {
                    // set error message
                    $errors = array('Job not found or not in the current group');
                } else {
                    // update the old previous and next group
                    $this->getAdapter()->update('Query_Jobs', array('next_id' => $nextId), array('id = ?' => $prevId));
                    $this->getAdapter()->update('Query_Jobs', array('prev_id' => $prevId), array('id = ?' => $nextId));

                    // update the new previous and next group
                    $this->getAdapter()->update('Query_Jobs', array('next_id' => $id), array('id = ?' => $prev['id']));
                    $this->getAdapter()->update('Query_Jobs', array('prev_id' => $id), array('id = ?' => $prev['next_id']));

                    // update current row
                    $this->getAdapter()->update('Query_Jobs', array('prev_id' => $prev['id'], 'next_id' => $prev['next_id']), array('id = ?' => $id));
                }
            }
        } else {
            // the group has changed

            // fetch the new group
            $select = $this->select(array(
                'where' => array(
                    'id = ?' => $newGroupId,
                    'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId()
                )
            ));
            $select->from('Query_Groups', array('id'));
            $group = $this->fetchOne($select);

            if ($group === false) {
                // set error message
                $errors = array('Group not found');

            } else {
                if (empty($newPrevId)) {
                    // it is the new first job of the group

                    // update previous and next rows at the old position
                    $this->getAdapter()->update('Query_Jobs', array('next_id' => $nextId), array('id = ?' => $prevId));
                    $this->getAdapter()->update('Query_Jobs', array('prev_id' => $prevId), array('id = ?' => $nextId));

                    // update the previously first job
                    $oldFirstId = $this->fetchFirstId($newGroupId);
                    $this->getAdapter()->update('Query_Jobs', array('prev_id' => $id), array('id = ?' => $oldFirstId));

                    // update current row
                    $this->getAdapter()->update('Query_Jobs', array(
                        'group_id' => $newGroupId,
                        'prev_id' => NULL,
                        'next_id' => $oldFirstId
                    ), array('id = ?' => $id));
                } else {
                    // a new previous job was also set

                    // fetch the new previous job
                    $prev = $this->fetchRow(array(
                        'where' => array(
                            'id = ?' => $newPrevId,
                            'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                            'group_id = ?' => $newGroupId,
                            'removed = 0'
                        )
                    ));

                    if ($prev === false) {
                        // set error message
                        $errors = array('Job not found or not in the new group');

                    } else {
                        // update the old previous and next group
                        $this->getAdapter()->update('Query_Jobs', array('next_id' => $nextId), array('id = ?' => $prevId));
                        $this->getAdapter()->update('Query_Jobs', array('prev_id' => $prevId), array('id = ?' => $nextId));

                        // update the new previous and next group
                        $this->getAdapter()->update('Query_Jobs', array('next_id' => $id), array('id = ?' => $prev['id']));
                        $this->getAdapter()->update('Query_Jobs', array('prev_id' => $id), array('id = ?' => $prev['next_id']));

                        // update current row
                        $this->getAdapter()->update('Query_Jobs', array(
                            'group_id' => $newGroupId,
                            'prev_id' => $prev['id'],
                            'next_id' => $prev['next_id']
                        ), array('id = ?' => $id));
                    }
                }
            }
        }
    }

    /**
     * Sets a job to the removed state
     * @param int $id              primary key of the job
     * @param int $removedStatusId id of the removed status of the query adapter
     */
    public function removeRow($id, $removedStatusId) {
        // get row
        $row = $this->fetchRow($id);

        // update previous and next rows
        $this->getAdapter()->update('Query_Jobs', array('next_id' => $row['next_id']), array('id = ?' => $row['prev_id']));
        $this->getAdapter()->update('Query_Jobs', array('prev_id' => $row['prev_id']), array('id = ?' => $row['next_id']));

        // remove the group
        $this->getAdapter()->update('Query_Jobs', array(
            'status_id' => $removedStatusId,
            'prev_status_id' => $row['status_id'],
            'group_id' => NULL,
            'prev_id' => NULL,
            'next_id' => NULL,
            'nrows' => 0,
            'size' => 0,
            'complete' => true,
            'removed' => true
        ), array('id = ?' => $id));
    }

    /**
     * Returns the id of the first job in a particular group
     * @param  int $groupId primary key of the group
     * @return int $id      primary key of the first job
     */
    public function fetchFirstId($group_id = NULL) {
        if (empty($group_id)) {
            $select = $this->select(array(
                'where' => array(
                    'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                    'group_id IS NULL',
                    'prev_id IS NULL',
                    'removed = 0'
                )
            ));
        } else {
            $select = $this->select(array(
                'where' => array(
                    'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                    'group_id = ?' => $group_id,
                    'prev_id IS NULL',
                    'removed = 0'
                )
            ));
        }
        $select->from('Query_Jobs', array('id'));

        $row = $this->fetchOne($select);
        if ($row === false) {
            return NULL;
        } else {
            return $row['id'];
        }
    }

    /**
     * Returns the id of the last job in a particular group
     * @param  int $groupId primary key of the group
     * @return int $id primary key of the last job
     */
    public function fetchLastId($group_id = NULL) {
        if (empty($group_id)) {
            $select = $this->select(array(
                'where' => array(
                    'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                    'group_id IS NULL',
                    'next_id IS NULL',
                    'removed = 0'
                )
            ));
        } else {
            $select = $this->select(array(
                'where' => array(
                    'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
                    'group_id = ?' => $group_id,
                    'next_id IS NULL',
                    'removed = 0'
                )
            ));
        }
        $select->from('Query_Jobs', array('id'));

        $row = $this->fetchOne($select);
        if ($row === false) {
            return NULL;
        } else {
            return $row['id'];
        }
    }
}