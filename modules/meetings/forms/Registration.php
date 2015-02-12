<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Meetings_Form_Registration extends Meetings_Form_Abstract {

    /**
     * The user which is logged in.
     * @var array
     */
    private $_user;

    /**
     * Sets $_user.
     * @param array $user the user which is logged in.
     */
    public function setUser($user) {
        $this->_user = $user;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // firstname, lastname and affiliation fields
        foreach (array('firstname','lastname','affiliation') as $key) {
            $this->addTextElement($key, array(
                'label' => ucfirst($key),
                'class' => 'span5',
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        // email field
        $this->addElement(new Meetings_Form_Element_Email(array(
            'meetingId' => $this->_meeting['id'],
            'class' => 'span5',
        )));

        // participant details
        $details = array();
        foreach ($this->_meeting['participant_detail_keys'] as $detailKey) {
            $details[] = $this->addParticipantDetailElement($detailKey,'span5');
        }

        // arrival and departure
        $this->addTextElement('arrival', array(
            'label' => 'Arrival',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('departure', array(
            'label' => 'Departure',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));

        $contributions = array();
        foreach ($this->_meeting['contribution_types'] as $contribution_type) {
            $contributions = array_merge($contributions, $this->addContributionElement($contribution_type));
        }

        // captcha and submit buttons
        if (empty($this->_user)) {
            // display captcha if no user is logged in
            $this->addCaptchaElement();
        }
        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $details),'personal', 'Your data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        if (!empty($contributions)) {
            $this->addHorizontalGroup($contributions,'contributions', 'Contributions');
        }
        if (empty($this->_user)) {
            $this->addHorizontalGroup(array('captcha'),'captcha');
        }
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_user['details']['firstname'])) {
            $this->setDefault('firstname', $this->_user['details']['firstname']);
            $this->setFieldReadonly('firstname');
        }
        if (isset($this->_user['details']['lastname'])) {
            $this->setDefault('lastname', $this->_user['details']['lastname']);
            $this->setFieldReadonly('lastname');
        }
        if (isset($this->_user['email'])) {
            $this->setDefault('email', $this->_user['email']);
            $this->setFieldReadonly('email');
        }
        $this->setDefault('arrival', $this->_meeting['begin']);
        $this->setDefault('departure', $this->_meeting['end']);
    }

}
