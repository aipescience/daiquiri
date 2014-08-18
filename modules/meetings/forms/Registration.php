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
            $this->addElement('text', $key, array(
                'label' => ucfirst($key),
                'required' => true,
                'filters' => array('StringTrim'),
                'validators' => array(
                    array('validator' => new Daiquiri_Form_Validator_Text()),
                )
            ));
        }

        // email fiels
        $field = new Meetings_Form_Element_Email(array(
            'meetingId' => $this->_meeting['id']
        ));
        $this->addElement($field);

        // participant details
        $participantDetailKeysElements = array();
        foreach ($this->_meeting['participant_detail_keys'] as $id => $detailKey) {
            switch (Meetings_Model_ParticipantDetailKeys::$types[$detailKey['type_id']]) {
                case "checkbox":
                    $this->addElement('multiCheckbox', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => explode(',',$detailKey['options'])
                    ));
                    break;
                case "radio":
                    $this->addElement('radio', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => explode(',',$detailKey['options'])
                    ));
                    break;
                case "select":
                    $this->addElement('select', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => explode(',',$detailKey['options'])
                    ));
                    break;
                case "multiselect":
                    $this->addElement('multiselect', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                        'required' => true,
                        'multiOptions' => explode(',',$detailKey['options'])
                    ));
                    break;
                default:
                    $this->addElement('text', $detailKey['key'], array(
                        'label' => ucfirst(str_replace('_',' ',$detailKey['key'])),
                        'required' => true,
                        'filters' => array('StringTrim'),
                        'validators' => array(
                            array('validator' => new Daiquiri_Form_Validator_Text()),
                        )
                    ));
            }

            $participantDetailKeysElements[] = $detailKey['key'];
        }

        // arrival and departure
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

        // contributions
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

        // captcha and submit buttons
        if (empty($this->_user)) {
            // display captcha if no user is logged in
            $this->addElement(new Daiquiri_Form_Element_Captcha('captcha'));
        }
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');


        // add groups
        $this->addHorizontalGroup(array_merge(array('firstname','lastname','affiliation','email'), $participantDetailKeysElements),'personal', 'Your data');
        if (!empty($this->_status)) {
            $this->addHorizontalGroup(array('status_id'),'status', 'Status');
        }
        $this->addHorizontalGroup(array('arrival','departure'),'attendance', 'Attendance');
        if (!empty($contributionElements)) {
            $this->addHorizontalGroup($contributionElements,'contributions', 'Contributions');
        }
        if (empty($this->_user)) {
            $this->addHorizontalCaptchaGroup('captcha');
        }
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('firstname', 'lastname', 'email') as $key) {
            if (isset($this->_user[$key])) {
                $this->setDefault($key, $this->_user[$key]);
                $this->setFieldReadonly($key);
            }
        }
        $this->setDefault('arrival', $this->_meeting['begin']);
        $this->setDefault('departure', $this->_meeting['end']);
    }

}
