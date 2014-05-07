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

class Meetings_Form_Element_Email extends Zend_Form_Element_Text {

    private $_unique = null;
    private $_meetingId = null;

    public function setMeetingId($meetingId) {
        $this->_meetingId = $meetingId;
    }

    public function setUnique($unique) {
        $this->_unique = $unique;
    }

    function init() {
        parent::init();

        $this->setLabel('Email');

        // add stuff
        $this->addFilter('StringTrim');
        $this->addValidator('emailAddress');
        
        if ($this->_unique) {
            $val = new Zend_Validate();
            $msg = 'The email is already in the database, please check if you are already registered.';

            $val1 = new Meetings_Form_Validate_Email('Meetings_Participants', 'email');
            $val1->setMessage($msg);
            $val1->setMeetingId($this->_meetingId);

            $val2 = new Meetings_Form_Validate_Email('Meetings_Registration', 'email');
            $val2->setMessage($msg);
            $val2->setMeetingId($this->_meetingId);

            // chainvalidators and add to field
            $val->addValidator($val1)->addValidator($val2);
            $this->addValidator($val);
        }
    }
}
