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

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Meetings_Contributions');
    }

    /**
     * Returns the colums of the contributions table.
     * @return array $cols
     */
    public function fetchCols() {
        $cols = parent::fetchCols();
        $cols[] = 'participant_firstname';
        $cols[] = 'participant_lastname';
        $cols[] = 'meeting_title';
        $cols[] = 'contribution_type_id';
        $cols[] = 'contribution_type';
        return $cols;
    }

    /**
     * Fetches a set of rows from the contributions table specified by $sqloptions.
     * @param array $sqloptions array of sqloptions (start,limit,order,where)
     * @return array $rows
     */
    public function fetchRows($sqloptions = array()) {
        $select = $this->select($sqloptions);
        $select->from('Meetings_Contributions');
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array('participant_firstname' => 'firstname','participant_lastname' => 'lastname'));
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array('meeting_title' => 'title'));
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id', array('contribution_type_id' => 'id', 'contribution_type' => 'contribution_type'));
        return $this->fetchAll($select);
    }

    /**
     * Fetches a specific row from the contributions table.
     * @param int $id primary key of the row
     * @throws Exception
     * @return array $row 
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Meetings_Contributions');
        $select->where('Meetings_Contributions.id = ?', $id);
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array('participant_email' => 'email'));
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array('meeting_id' => 'id', 'meeting_title' => 'title'));
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id');
        
        return $this->fetchOne($select);
    }

    /**
     * Counts the number of rows of the contributions table.
     * @param array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return int $count
     */
    public function countRows(array $sqloptions = null) {
        // get select object
        $select = $this->select();
        $select->from('Meetings_Contributions', 'COUNT(*) as count');
        $select->join('Meetings_Participants', 'Meetings_Contributions.participant_id = Meetings_Participants.id', array());
        $select->join('Meetings_Meetings', 'Meetings_Participants.meeting_id = Meetings_Meetings.id', array());
        $select->join('Meetings_ContributionTypes', 'Meetings_Contributions.contribution_type_id = Meetings_ContributionTypes.id', array());

        if ($sqloptions) {
            if (isset($sqloptions['where'])) {
                foreach ($sqloptions['where'] as $w) {
                    $select = $select->where($w);
                }
            }
            if (isset($sqloptions['orWhere'])) {
                foreach ($sqloptions['orWhere'] as $w) {
                    $select = $select->orWhere($w);
                }
            }
        }

        // query database
        $row = $this->fetchOne($select);
        return (int) $row['count'];
    }
}