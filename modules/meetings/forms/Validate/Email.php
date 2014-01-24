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

class Meetings_Form_Validate_Email extends Zend_Validate_Db_NoRecordExists {

    private $_meetingId = null;

    public function setMeetingId($meetingId) {
        $this->_meetingId = $meetingId;
    }

    public function getSelect() {
        if (null === $this->_select) {
            $db = $this->getAdapter();

            $select = new Zend_Db_Select($db);
            $select->from($this->_table, array($this->_field), $this->_schema);
            if ($db->supportsParameters('named')) {
                $select->where($db->quoteIdentifier($this->_field, true).' = :value'); // named
            } else {
                $select->where($db->quoteIdentifier($this->_field, true).' = ?'); // positional
            }
            $select->limit(1);
            if ($this->_meetingId !== null) {
                $select->where($db->quoteInto('`meeting_id` = ?', $this->_meetingId));
            }

            $this->_select = $select;
        }
        return $this->_select;
    }
}