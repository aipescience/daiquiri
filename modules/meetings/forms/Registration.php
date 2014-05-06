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

class Meetings_Form_Registration extends Daiquiri_Form_Abstract {

    private $_submit;
    private $_entry;
    private $_meeting;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function setMeeting($meeting) {
        $this->_meeting = $meeting;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        foreach (array('firstname','lastname','affiliation') as $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst($key),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        if (empty($this->_entry)) {
            $field = new Meetings_Form_Element_Email('email', array(
                'required' => true,
                'unique' => true,
                'meetingId' => $this->_meeting['id']
            ));
        } else {
            $field = new Meetings_Form_Element_Email('email', array(
                'required' => true,
                'meetingId' => $this->_meeting['id']
            ));
        }
        $this->addElement($field);

        foreach ($this->_meeting['participant_detail_keys'] as $id => $key) {
            $this->addElement('text', $key, array(
                'label' => ucfirst(str_replace('_',' ',$key)),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $elements[] = $key;
        }
        $this->addElement('text', 'arrival', array(
            'label' => 'Arrival',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'departure', array(
            'label' => 'Departure',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));

        $contributionElements = array();
        foreach ($this->_meeting['contribution_types'] as $id => $contribution_type) {
            $this->addElement('checkbox', $contribution_type . '_bool', array(
                'class' => 'daiquiri-toggle-control-group',
                'label' => ucfirst($contribution_type),
            ));
            $this->addElement('text', $contribution_type . '_title', array(
                'label' => 'Title',
                'class' => 'input-xxlarge daiquiri-hide-control-group',
                'required' => false,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
            $this->addElement('textarea', $contribution_type . '_abstract', array(
                'label' => 'Abstract',
                'class' => 'input-xxlarge daiquiri-hide-control-group',
                'rows' => 6,
                'required' => false,
                'filters' => array('StringTrim')
            ));
            $contributionElements[] = $contribution_type . '_bool';
            $contributionElements[] = $contribution_type . '_title';
            $contributionElements[] = $contribution_type . '_abstract';
        }

        $this->addElement(new Daiquiri_Form_Element_Captcha('captcha'));

        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $this->_meeting['participant_detail_keys']),'personal', 'Personal data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        if (!empty($contributionElements)) {
            $this->addHorizontalGroup($contributionElements,'contributions', 'Contributions');
        }
        $this->addCaptchaGroup('captcha');
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        $this->setDefault('arrival', $this->_meeting['begin']);
        $this->setDefault('departure', $this->_meeting['end']);
    }

}
