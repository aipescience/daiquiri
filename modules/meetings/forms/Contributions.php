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

class Meetings_Form_Contributions extends Meetings_Form_Abstract {

    /**
     * The different participants for this meeting.
     * @var array
     */
    private $_participants;

    /**
     * Sets $_participants.
     * @param array $participants the different participants for this meeting.
     */
    public function setParticipants($participants) {
        $this->_participants = $participants;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
        
        // add elements
        $this->addElement('select', 'participant_id', array(
            'label' => 'Participant',
            'required' => true,
            'multiOptions' => $this->_participants,
        ));
        $this->addElement('select', 'contribution_type_id', array(
            'label' => 'Contribution type',
            'required' => true,
            'multiOptions' => $this->_meeting['contribution_types'],
        ));
        $this->addElement('text', 'title', array(
            'label' => 'Title',
            'class' => 'input-xxlarge',
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('textarea', 'abstract', array(
            'label' => 'Abstract',
            'class' => 'input-xxlarge',
            'rows' => 6,
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));
        
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('participant_id','contribution_type_id','title','abstract'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('contribution_type_id','title','abstract') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
    }

}
