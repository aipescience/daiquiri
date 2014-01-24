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

class Meetings_Form_Element_Email extends Zend_Form_Element_Text {

    private $_meetingId = null;

    public function setMeetingId($meetingId) {
        $this->_meetingId = $meetingId;
    }

    function init() {
        parent::init();

        $this->setLabel('Email');

        // add stuff
        $this->addFilter('StringTrim');
        $this->addValidator('emailAddress');

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
