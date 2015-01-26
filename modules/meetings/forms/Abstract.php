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

abstract class Meetings_Form_Abstract extends Daiquiri_Form_Model {

    /**
     * The meeting for this form.
     * @var array
     */
    protected $_meeting;

    /**
     * Sets $_meeting.
     * @param array $meeting the meeting for this form
     */
    public function setMeeting($meeting) {
        $this->_meeting = $meeting;
    }

    /**
     * Creates a form element for a participant detail and adds it to the form.
     * @param  array  $detailKey description of the participant detail
     * @return string $name      name of the element
     */
    public function addParticipantDetailElement($detailKey, $class = '') {

        switch (Meetings_Model_ParticipantDetailKeys::$types[$detailKey['type_id']]) {
            case "checkbox":
                $this->addMultiCheckboxElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'multiOptions' => Zend_Json::decode($detailKey['options'])
                ));
                break;
            case "radio":
                $this->addRadioElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'required' => true,
                    'multiOptions' => Zend_Json::decode($detailKey['options'])
                ));
                break;
            case "select":
                $this->addSelectElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'required' => true,
                    'multiOptions' => Zend_Json::decode($detailKey['options'])
                ));
                break;
            case "multiselect":
                $this->addMultiselectElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'multiOptions' => Zend_Json::decode($detailKey['options'])
                ));
                break;
            default:
                $this->addTextElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ',$detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'class' => $class,
                    'required' => true,
                    'filters' => array('StringTrim'),
                    'validators' => array(
                        array('validator' => new Daiquiri_Form_Validator_Text()),
                    )
                ));
        }

        return $detailKey['key'];
    }

    /**
     * Creates form elements for the contribution type and adds it to the form.
     * @param  array  $contributionType name of the contribution type
     * @return array  $names            names of the elements
     */
    public function addContributionElement($contributionType) {

        $this->addCheckboxElement($contributionType . '_bool', array(
            'label' => ucfirst($contributionType)
        ));
        $this->addTextElement($contributionType . '_title', array(
            'label' => 'Title',
            'required' => false,
            'class' => 'span6 mono',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextareaElement($contributionType . '_abstract', array(
            'label' => 'Abstract',
            'class' => 'span6 mono',
            'rows' => 6,
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));

        return array(
            $contributionType . '_bool',
            $contributionType . '_title',
            $contributionType . '_abstract'
        );
    }
}
