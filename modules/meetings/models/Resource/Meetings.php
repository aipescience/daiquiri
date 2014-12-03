<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

class Meetings_Model_Resource_Meetings extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Meetings_Meetings');
    }

    /**
     * Fetches a set of rows specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        // get select object
        $select = $this->select();
        $select->from('Meetings_Meetings');

        // order by begin
        $select->order('begin DESC');

        // get result convert to array and return
        return $this->fetchAll($select);
    }

    /**
     * Fetches one row specified by its primary key or an array of sqloptions.
     * @param mixed $input primary key of the row OR array of sqloptions
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($input) {
        if (empty($input)) {
            throw new Exception('$id or $sqloptions not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        if (is_array($input)) {
            $select = $this->select($input);
            $select->from('Meetings_Meetings');
        } else {
            $select = $this->select();
            $select->from('Meetings_Meetings');
            $select->where('Meetings_Meetings.id = ?', $input);
        }

        $row = $this->fetchOne($select);
        if (empty($row)) {
            return array();
        } else {
            return array_merge(
                $row,
                array('contribution_types' => $this->_fetchMeetingsContributionTypes($row['id'])),
                array('participant_detail_keys' => $this->_fetchMeetingsParticipantDetailKeys($row['id']))
            );
        }
    }

    /**
     * Inserts a new meeting.
     * Returns the primary key of the new row.
     * @param array $data row data
     * @throws Exception
     * @return int $id id of the new user
     */
    public function insertRow(array $data = array()) {
        if (empty($data)) {
            throw new Exception('$data not provided in ' . get_class($this) . '::insertRow()');
        }

        $contribution_type_ids = $data['contribution_type_id'];
        unset($data['contribution_type_id']);

        $participant_detail_key_ids = $data['participant_detail_key_id'];
        unset($data['participant_detail_key_id']);

        // insert values
        $this->getAdapter()->insert('Meetings_Meetings', $data);

        // get id
        $id = $this->getAdapter()->lastInsertId();

        // create new contribution types for this meeting
        $this->_insertMeetingsContributionTypes($id, $contribution_type_ids);

        // create new participant detail keys for this meeting
        $this->_insertMeetingsParticipantDetailKeys($id, $participant_detail_key_ids);

        return $id;
    }

    /**
     * Updates a meeting.
     * @param int $id id of the meeting
     * @param array $data row data
     * @throws Exception
     */
    public function updateRow($id, $data) {
        if (empty($id) || empty($data)) {
            throw new Exception('$id or $data not provided in ' . get_class($this) . '::insertRow()');
        }

        $contribution_type_ids = $data['contribution_type_id'];
        unset($data['contribution_type_id']);

        $participant_detail_key_ids = $data['participant_detail_key_id'];
        unset($data['participant_detail_key_id']);

        // update the row in the database
        $this->getAdapter()->update('Meetings_Meetings', $data, array('id = ?' => $id));

        // delete old and create new contribution types for this meeting
        $this->_deleteMeetingsContributionTypes($id);
        $this->_insertMeetingsContributionTypes($id, $contribution_type_ids);

        // delete old and create new participant detail keys for this meeting
        $this->_deleteMeetingsParticipantDetailKeys($id);
        $this->_insertMeetingsParticipantDetailKeys($id, $participant_detail_key_ids);
    }

    /**
     * Deletes a meeting.
     * @param int $id 
     * @throws Exception
     */
    public function deleteRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::deleteRow()');
        }

        // delete the meeting
        $this->getAdapter()->delete('Meetings_Meetings', array('id = ?' => $id));

        // delete the contribution types and participant detail keys for this meeting
        $this->_deleteMeetingsContributionTypes($id);
        $this->_deleteMeetingsParticipantDetailKeys($id);
    }

    /**
     * Fetches the contribution types for a meeting.
     * @param int $id id of the meeting
     * @return array $contributionTypes
     */
    private function _fetchMeetingsContributionTypes($id) {
        $select = $this->select();
        $select->from('Meetings_Meetings_ContributionTypes', array('contribution_type_id'));
        $select->where('Meetings_Meetings_ContributionTypes.meeting_id = ?', $id);
        $select->join('Meetings_ContributionTypes', 'Meetings_Meetings_ContributionTypes.contribution_type_id = Meetings_ContributionTypes.id', array('contribution_type'));

        $contributionTypes = array();
        foreach($this->fetchAll($select) as $row) {
            $contributionTypes[$row['contribution_type_id']] = $row['contribution_type'];
        }
        return $contributionTypes;
    }

    /**
     * Inserts the contribution types for a meeting.
     * @param int $id id of the meeting
     * @param int $contributionTypeIds ids of the contribution types
     */
    private function _insertMeetingsContributionTypes($id, $contributionTypeIds) {
        if (!empty($contributionTypeIds)) {
            foreach($contributionTypeIds as $contributionTypeId) {
                $this->getAdapter()->insert('Meetings_Meetings_ContributionTypes', array(
                    'meeting_id' => $id,
                    'contribution_type_id' => $contributionTypeId
                ));
            }
        }
    }

    /**
     * Deletes the contribution types for a meeting.
     * @param int $id id of the meeting
     */
    private function _deleteMeetingsContributionTypes($id) {
        $this->getAdapter()->delete('Meetings_Meetings_ContributionTypes', array('meeting_id = ?' => $id));
    }

    /**
     * Fetches the participant detail keys for a meeting.
     * @param int $id id of the meeting
     * @return array $participantDetailKeys
     */
    private function _fetchMeetingsParticipantDetailKeys($id) {
        $select = $this->select();
        $select->from('Meetings_Meetings_ParticipantDetailKeys', array('participant_detail_key_id'));
        $select->where('Meetings_Meetings_ParticipantDetailKeys.meeting_id = ?', $id);
        $select->join('Meetings_ParticipantDetailKeys', 'Meetings_Meetings_ParticipantDetailKeys.participant_detail_key_id = Meetings_ParticipantDetailKeys.id', array('key','hint','type_id','options'));

        $participantDetailKeys = array();
        foreach($this->fetchAll($select) as $row) {
            $participantDetailKeys[$row['participant_detail_key_id']] = array(
                'key' => $row['key'],
                'hint' => $row['hint'],
                'type_id' => $row['type_id'],
                'options' => $row['options']
            );
        }
        return $participantDetailKeys;
    }

    /**
     * Inserts the participant detail keys for a meeting.
     * @param int $id id of the meeting
     * @param int $participantDetailKeyIds ids of the participant detail keys
     */
    private function _insertMeetingsParticipantDetailKeys($id, $participantDetailKeyIds) {
        if (!empty($participantDetailKeyIds)) {
            foreach($participantDetailKeyIds as $participantDetailKeyId) {
                $this->getAdapter()->insert('Meetings_Meetings_ParticipantDetailKeys', array(
                    'meeting_id' => $id,
                    'participant_detail_key_id' => $participantDetailKeyId
                ));
            }
        }
    }

    /**
     * Deletes the participant detail keys for a meeting.
     * @param int $id id of the meeting
     */
    private function _deleteMeetingsParticipantDetailKeys($id) {
        $this->getAdapter()->delete('Meetings_Meetings_ParticipantDetailKeys', array('meeting_id = ?' => $id));
    }
}