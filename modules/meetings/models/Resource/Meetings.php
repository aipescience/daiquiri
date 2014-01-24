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

class Meetings_Model_Resource_Meetings extends Daiquiri_Model_Resource_Simple {

    public function fetchRows() {
        // get select object
        $select = $this->getAdapter()->select();
        $select->from('Meetings_Meetings');

        // order by begin
        $select->order('begin ASC');

        // get result convert to array and return
        return $this->getAdapter()->fetchAll($select);
    }

    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        $select = $this->getAdapter()->select();
        $select->from('Meetings_Meetings');
        $select->where('Meetings_Meetings.id = ?', $id);

        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception($id . ' not found in ' . get_class($this) . '::fetchRow()');
        }

        return array_merge(
            $row,
            array('contribution_types' => $this->_fetchMeetingsContributionTypes($id)),
            array('participant_detail_keys' => $this->_fetchMeetingsParticipantDetailKeys($id))
        );
    }

    public function insertRow(array $data) {
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

    private function _fetchMeetingsContributionTypes($id) {
        $select = $this->getAdapter()->select();
        $select->from('Meetings_Meetings_ContributionTypes', array('contribution_type_id'));
        $select->where('Meetings_Meetings_ContributionTypes.meeting_id = ?', $id);
        $select->join('Meetings_ContributionTypes', 'Meetings_Meetings_ContributionTypes.contribution_type_id = Meetings_ContributionTypes.id', array('contribution_type'));

        $data = array();
        foreach($this->getAdapter()->fetchAll($select) as $row) {
            $data[$row['contribution_type_id']] = $row['contribution_type'];
        }
        return $data;
    }

    private function _insertMeetingsContributionTypes($id, $contribution_type_ids) {
        if (!empty($contribution_type_ids)) {
            foreach($contribution_type_ids as $contribution_type_id) {
                $this->getAdapter()->insert('Meetings_Meetings_ContributionTypes', array(
                    'meeting_id' => $id,
                    'contribution_type_id' => $contribution_type_id
                ));
            }
        }
    }

    private function _deleteMeetingsContributionTypes($id) {
        $this->getAdapter()->delete('Meetings_Meetings_ContributionTypes', array('meeting_id = ?' => $id));
    }

    private function _fetchMeetingsParticipantDetailKeys($id) {
        $select = $this->getAdapter()->select();
        $select->from('Meetings_Meetings_ParticipantDetailKeys', array('participant_detail_key_id'));
        $select->where('Meetings_Meetings_ParticipantDetailKeys.meeting_id = ?', $id);
        $select->join('Meetings_ParticipantDetailKeys', 'Meetings_Meetings_ParticipantDetailKeys.participant_detail_key_id = Meetings_ParticipantDetailKeys.id', array('key'));

        $data = array();
        foreach($this->getAdapter()->fetchAll($select) as $row) {
            $data[$row['participant_detail_key_id']] = $row['key'];
        }
        return $data;
    }

    private function _insertMeetingsParticipantDetailKeys($id, $participant_detail_key_ids) {
        if (!empty($participant_detail_key_ids)) {
            foreach($participant_detail_key_ids as $participant_detail_key_id) {
                $this->getAdapter()->insert('Meetings_Meetings_ParticipantDetailKeys', array(
                    'meeting_id' => $id,
                    'participant_detail_key_id' => $participant_detail_key_id
                ));
            }
        }
    }

    private function _deleteMeetingsParticipantDetailKeys($id) {
        $this->getAdapter()->delete('Meetings_Meetings_ParticipantDetailKeys', array('meeting_id = ?' => $id));
    }
}