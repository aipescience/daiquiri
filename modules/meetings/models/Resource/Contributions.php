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

class Meetings_Model_Resource_Contributions extends Daiquiri_Model_Resource_Table {

    public function __construct() {
        $this->setTablename('Meetings_Contributions');
    }

    public function fetchCols() {
        $cols = parent::fetchCols();
        $cols[] = 'participant_firstname';
        $cols[] = 'participant_lastname';
        $cols[] = 'meeting_title';
        $cols[] = 'contribution_type_id';
        $cols[] = 'contribution_type';
        return $cols;
    }

    public function fetchRows($sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from('Meetings_Contributions');
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array('participant_firstname' => 'firstname','participant_lastname' => 'lastname'));
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array('meeting_title' => 'title'));
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id', array('contribution_type_id' => 'id', 'contribution_type' => 'contribution_type'));
        return $this->getAdapter()->fetchAll($select);
    }

    public function countRows(array $sqloptions = null) {
        // get select object
        $select = $this->select($sqloptions);
        $select->from('Meetings_Contributions', 'COUNT(*) as count');
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array());
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array());
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id', array());

        // query database
        $row = $this->getAdapter()->fetchRow($select);
        return (int) $row['count'];
    }

    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::fetchRow()');
        }

        $select = $this->getAdapter()->select();
        $select->from('Meetings_Contributions');
        $select->where('Meetings_Contributions.id = ?', $id);
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array('participant_email' => 'email'));
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array('meeting_id' => 'id', 'meeting_title' => 'title'));
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id');
        
        $row = $this->getAdapter()->fetchRow($select);
        if (empty($row)) {
            throw new Exception($id . ' not found in ' . get_class($this) . '::fetchRow()');
        }

        return $row;
    }
}