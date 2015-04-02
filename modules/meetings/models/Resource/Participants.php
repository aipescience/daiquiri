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

class Meetings_Model_Resource_Participants extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Meetings_Participants');
    }

    /**
     * Returns the colums of the joined participants table.
     * @return array $cols
     */
    public function fetchCols() {
        $cols = parent::fetchCols();
        $cols['meeting_title'] = $this->quoteIdentifier('Meetings_Meetings','meeting_title');
        $cols['status'] = $this->quoteIdentifier('Meetings_ParticipantStatus','status');
        return $cols;
    }

    /**
     * Fetches a set of rows from the participants table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from($this->getTablename());
        $select->join('Meetings_Meetings','Meetings_Meetings.id = Meetings_Participants.meeting_id',array('meeting_title' => 'title','meeting_id' => 'id'));
        $select->join('Meetings_ParticipantStatus','Meetings_ParticipantStatus.id = Meetings_Participants.status_id',array('status' => 'status'));

        return $this->fetchAll($select);
    }

    /**
     * Counts the number of rows in participants table.
     * @param @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null) {
        // get select object
        $select = $this->select();
        $select->from($this->getTablename(), 'COUNT(*) as count');
        $select->join('Meetings_Meetings','Meetings_Meetings.id = Meetings_Participants.meeting_id',array());
        $select->join('Meetings_ParticipantStatus','Meetings_ParticipantStatus.id = Meetings_Participants.status_id', array());

        if ($sqloptions) {
            $select->setWhere($sqloptions);
            $select->setOrWhere($sqloptions);
        }

        // query database
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }

    /**
     * Fetches a specific row from the participants table.
     * @param mixed $id primary key of the row
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        // basic sql query including where conditions from $sqloptions
        $select = $this->select();
        $select->from('Meetings_Participants');
        $select->where('Meetings_Participants.id = ?', $id);
        $select->join('Meetings_ParticipantStatus', 'Meetings_ParticipantStatus.id = Meetings_Participants.status_id', array('status'));

        $row = $this->fetchOne($select);

        if (empty($row)) {
            return false;
        }

        // fetch details
        $select = $this->select();
        $select->from('Meetings_ParticipantDetails', array('value'));
        $select->join('Meetings_ParticipantDetailKeys', 'Meetings_ParticipantDetailKeys.id = Meetings_ParticipantDetails.key_id', array('key'));
        $select->where('Meetings_ParticipantDetails.participant_id = ?', $id);

        $row['details'] = array();
        foreach($this->fetchAll($select) as $r) {
            $row['details'][$r['key']] = $r['value'];
        }

        // fetch contributions
        $select = $this->select();
        $select->from('Meetings_Contributions', array('title','abstract','contribution_type_id'));
        $select->join('Meetings_ContributionTypes', 'Meetings_ContributionTypes.id = Meetings_Contributions.contribution_type_id', array('contribution_type'));
        $select->where('Meetings_Contributions.participant_id = ?', $id);

        $row['contributions'] = array();
        foreach($this->fetchAll($select) as $r) {
            $row['contributions'][$r['contribution_type']] = array(
                'title' => $r['title'],
                'abstract' => $r['abstract']
            );
        }

        return $row;
    }

    /**
     * Inserts a participant.
     * Returns the primary key of the new row.
     * @param array $data row data
     * @throws Exception
     * @return int $id id of the new row
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::insertRow()');
        }

        $contributions = $data['contributions'];
        unset($data['contributions']);

        $details = $data['details'];
        unset($data['details']);

        // insert values
        $this->getAdapter()->insert('Meetings_Participants', $data);

        // get id
        $id = $this->getAdapter()->lastInsertId();

        // update details
        foreach ($details as $key_id => $value) {
            $this->getAdapter()->insert('Meetings_ParticipantDetails', array(
                'participant_id' => $id,
                'key_id' =>  $key_id,
                'value' => $value
            ));
        }

        // update contributions
        foreach ($contributions as $contribution_type_id => $contribution) {
            if ($contribution !== false) {
                $this->getAdapter()->insert('Meetings_Contributions', array(
                    'participant_id' => $id,
                    'contribution_type_id' =>  $contribution_type_id,
                    'title' => $contribution['title'],
                    'abstract' => $contribution['abstract'],
                    'accepted' => 0
                ));
            }
        }

        return $id;
    }

    /**
     * Updates a participant.
     * @param int $id id of the participant
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, $data) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::insertRow()');
        }

        if (isset($data['details'])) {
            $details = $data['details'];
            unset($data['details']);
        }

        if (isset($data['contributions'])) {
            $contributions = $data['contributions'];
            unset($data['contributions']);
        }

        // update the row in the database
        $this->getAdapter()->update('Meetings_Participants', $data, array('id = ?' => $id));

        // update details
        foreach ($details as $key_id => $value) {
            $select = $this->getAdapter()->select();
            $select->from('Meetings_ParticipantDetails');
            $select->where('participant_id=?',$id);
            $select->where('key_id=?', $key_id);

            $row = $this->getAdapter()->fetchRow($select);

            if (empty($row)) {
                $this->getAdapter()->insert('Meetings_ParticipantDetails', array(
                    'participant_id' => $id,
                    'key_id' =>  $key_id,
                    'value' => $value
                ));
            } else {
                $this->getAdapter()->update('Meetings_ParticipantDetails', array(
                    'value' => $value
                ), array(
                    'participant_id=?' => $id,
                    'key_id=?' =>  $key_id
                ));
            }
        }

        // update contributions
        foreach ($contributions as $contribution_type_id => $contribution) {
            $select = $this->getAdapter()->select();
            $select->from('Meetings_Contributions');
            $select->where('participant_id=?',$id);
            $select->where('contribution_type_id=?',$contribution_type_id);

            $row = $this->getAdapter()->fetchRow($select);

            if (empty($row)) {
                if ($contribution !== false) {
                    $this->getAdapter()->insert('Meetings_Contributions', array(
                        'participant_id' => $id,
                        'contribution_type_id' =>  $contribution_type_id,
                        'title' => $contribution['title'],
                        'abstract' => $contribution['abstract'],
                        'accepted' => 0
                    ));
                }
            } else {
                if ($contribution !== false) {
                    $this->getAdapter()->update('Meetings_Contributions', array(
                        'title' => $contribution['title'],
                        'abstract' => $contribution['abstract']
                    ), array(
                        'participant_id=?' => $id,
                        'contribution_type_id=?' => $contribution_type_id
                    ));
                } else {
                    $this->getAdapter()->delete('Meetings_Contributions', array(
                        'participant_id=?' => $id,
                        'contribution_type_id=?' => $contribution_type_id
                    ));
                }
            }
        }

        return $id;
    }

    /**
     * Deletes a participant.
     * @param int $id id of the participant
     * @throws Exception
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::deleteRow()');
        }

        // delete the row
        $this->getAdapter()->delete('Meetings_Participants', array('id = ?' => $id));

        // delete all details and contributions for this participant
        $this->getAdapter()->delete('Meetings_ParticipantDetails', array('participant_id = ?' => $id));
        $this->getAdapter()->delete('Meetings_Contributions', array('participant_id = ?' => $id));
    }
}