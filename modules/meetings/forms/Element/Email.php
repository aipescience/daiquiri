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

class Meetings_Form_Element_Email extends Daiquiri_Form_Element_Text {

    /**
     * The id of the meeting for this element.
     * @var int
     */
    private $_meetingId = null;

    /**
     * Sets $_meetingId.
     * @param int $meetingId the id of the meeting for this element
     */
    public function setMeetingId($meetingId) {
        $this->_meetingId = $meetingId;
    }

    /**
     * Exclude a certain id from the unique-ness validator.
     * @var int
     */
    protected $_excludeId = false;

    /**
     * Sets $_excludeId.
     * @param bool $unique exclude a certain id from the unique-ness validator.
     */
    public function setExcludeId($excludeId) {
        $this->_excludeId = $excludeId;
    }

    /**
     * Construtor. Sets the name of the element.
     * @param array $options form options for this element
     */
    public function __construct($options = null) {
        parent::__construct('email', $options);
    }

    /**
     * Initializes the form element.
     */
    function init() {
        $this->setLabel('Email');

        // set filter
        $this->addFilter('StringTrim');

        // add validator for max string length
        $this->addValidator('StringLength', false, array(0, 256));

        // add validator for email addresses
        $this->addValidator('emailAddress');

        // add validator for beeing unique in the database
        $validator = new Zend_Validate();
        $message = 'The email is already in the database, please check if you are already registered.';

        $participantsTableValidator = new Meetings_Form_Validate_Email('Meetings_Participants', 'email');
        $participantsTableValidator->setMessage($message);
        $participantsTableValidator->setMeetingId($this->_meetingId);
        if (!empty($this->_excludeId)) {
            $participantsTableValidator->setExcludeId($this->_excludeId);
        }

        $registrationTableValidator = new Meetings_Form_Validate_Email('Meetings_Registration', 'email');
        $registrationTableValidator->setMessage($message);
        $registrationTableValidator->setMeetingId($this->_meetingId);

        // chainvalidators and add to field
        $validator->addValidator($participantsTableValidator)->addValidator($registrationTableValidator);
        $this->addValidator($validator);
    }
}
