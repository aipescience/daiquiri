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