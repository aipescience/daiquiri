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

class Meetings_Form_Meetings extends Daiquiri_Form_Model {

    /**
     * The different contribution types for this meeting.
     * @var array
     */
    private $_contributionTypes = array();

    /**
     * The different participant detail keys for this meeting.
     * @var array
     */
    private $_participantDetailKeys = array();

    /**
     * The different publication roles to choose from.
     * @var array
     */
    private $_roles = array();

    /**
     * Sets $_contributionTypes.
     * @param array $contributionTypes the different contribution types for this meeting
     */
    public function setContributionTypes($contributionTypes) {
        foreach($contributionTypes as $key => $value) {
            $this->_contributionTypes[$key] = ucfirst($value);
        }
    }

    /**
     * Sets $_participantDetailKeys.
     * @param array $participantDetailKeys the different participant detail keys for this meeting
     */
    public function setParticipantDetailKeys($participantDetailKeys) {
        foreach($participantDetailKeys as $key => $value) {
            $this->_participantDetailKeys[$key] = ucfirst($value);
        }
    }

    /**
     * Sets $_roles.
     * @param array $roles the different publication roles to choose from
     */
    public function setRoles($roles) {
        foreach($roles as $key => $value) {
            if ($key > 0) {
                $this->_roles[$key] = 'published for ' . $value;
            } else {
                $this->_roles[$key] = $value;
            }
        }
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
        
        // add elements
        $this->addTextElement('title', array(
            'label' => 'Title of the Meeting',
            'class' => 'span6 mono',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('slug', array(
            'label' => 'Short title for URL',
            'required' => true,
            'class' => 'mono',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Zend_Validate_Regex(array('pattern' => '/[a-zA-Z0-9\-]*/'))),
            )
        ));
        $this->addTextareaElement('description', array(
            'label' => 'Description',
            'class' => 'span6 mono',
            'rows' => 4,
            'filters' => array('StringTrim')
        ));
        $this->addTextElement('begin', array(
            'label' => 'First day of the meeting',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('end', array(
            'label' => 'Last day of the meeting',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextareaElement('registration_message', array(
            'label' => 'Registration messsage',
            'rows' => 6,
            'class' => 'span6 mono',
            'filters' => array('StringTrim')
        ));
        $this->addTextareaElement('participants_message', array(
            'label' => 'Participants messsage',
            'rows' => 6,
            'class' => 'span6 mono',
            'filters' => array('StringTrim')
        ));
        $this->addTextareaElement('contributions_message', array(
            'label' => 'Contributions messsage',
            'rows' => 6,
            'class' => 'span6 mono',
            'filters' => array('StringTrim')
        ));
        $this->addSelectElement('registration_publication_role_id', array(
            'label' => 'Registration form',
            'required' => true,
            'multiOptions' => $this->_roles
        ));
        $this->addSelectElement('participants_publication_role_id', array(
            'label' => 'Participants list',
            'required' => true,
            'multiOptions' => $this->_roles
        ));
        $this->addSelectElement('contributions_publication_role_id', array(
            'label' => 'Contributions list',
            'required' => true,
            'multiOptions' => $this->_roles
        ));
        $this->addMultiCheckboxElement('contribution_type_id', array(
            'label' => 'Contribution types',
            'multiOptions' => $this->_contributionTypes
        ));
        $this->addMultiCheckboxElement('participant_detail_key_id', array(
            'label' => 'Requested details from participlants',
            'multiOptions' => $this->_participantDetailKeys
        ));

        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('title','slug','description','begin','end','registration_message','participants_message','contributions_message','registration_publication_role_id','participants_publication_role_id','contributions_publication_role_id','contribution_type_id','participant_detail_key_id'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('title','slug','description','begin','end','registration_publication_role_id','participants_publication_role_id','contributions_publication_role_id') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
        if (isset($this->_entry['registration_message'])) {
            $this->setDefault('registration_message', $this->_entry['registration_message']);
        } else {
            $this->setDefault('registration_message', '<h2>Registration</h2>');
        }
        if (isset($this->_entry['participants_message'])) {
            $this->setDefault('participants_message', $this->_entry['participants_message']);
        } else {
            $this->setDefault('participants_message', '<h2>Participants</h2>');
        }
        if (isset($this->_entry['contributions_message'])) {
            $this->setDefault('contributions_message', $this->_entry['contributions_message']);
        } else {
            $this->setDefault('contributions_message', '<h2>Contributions</h2>');
        }

        if (isset($this->_entry['contribution_types'])) {
            $this->getElement('contribution_type_id')->setValue(array_keys($this->_entry['contribution_types']));
        }
        if (isset($this->_entry['participant_detail_keys'])) {
            $this->getElement('participant_detail_key_id')->setValue(array_keys($this->_entry['participant_detail_keys']));
        }
    }

}
