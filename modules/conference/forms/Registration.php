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

/**
 * @brief Build the registration form
 * @author Kristin Riebe
 */
class Application_Model_Form_Register extends Daiquiri_Form_Abstract {

    protected $_positions = array('', 'staff', 'postdoc', 'student', 'other');
    protected $_days = array('', '<13', '13', '14', '15', '16', '17', '>17');

    public function init() {
        parent::init();

        $textVal = new Daiquiri_Form_Validator_Text();

        $this->addPrefixPath('Daiquiri_Form', 'Daiquiri/Core/Form/');

        $this->addElement('text', 'firstname', array(
            'label' => 'First Name:',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => $textVal)
            )
        ));
        $this->addElement('text', 'lastname', array(
            'label' => 'Last Name:',
            //'description' => 'Please enter your last name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => $textVal),
            )
        ));
        $this->addElement('text', 'email', array(
            'label' => 'Email:',
            //'description' => 'Example: user@example.com',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'EmailAddress'),
            )
        ));
        $this->addElement('text', 'institute', array(
            'label' => 'Institution:',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => $textVal),
            )
        ));
        $this->addElement('select', 'position', array(
            'label' => 'Position:',
            'multiOptions' => $this->_positions,
            'cols' => '30',
            'required' => true,
        ));


        $this->addElement('select', 'arrival', array(
            'label' => 'Arrival: 2012, November ',
            'multiOptions' => $this->_days,
            'cols' => '30',
            'required' => true
        ));
        $this->addElement('select', 'departure', array(
            'label' => 'Departure: 2012, November ',
            'multiOptions' => $this->_days,
            'cols' => '30',
            'required' => true
        ));
        $this->addElement('text', 'hotel', array(
            'label' => 'Hotel:',
            'description' => 'Please book the hotel yourself!',
            'size' => '30',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => $textVal),
            )
        ));


        $this->addElement('radio', 'presentationflag', array(
            'label' => 'Talk:',
            'multiOptions' => array('1' => 'yes', '0' => 'no'),
            'size' => '30',
            'required' => true
        ));
        $this->addElement('text', 'title', array(
            'label' => 'Title:',
            'size' => '30',
            //'required'   => true, // should only be required, if Talk is yes --> jQuery
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => $textVal),
            ),
        ));
        $this->addElement('textarea', 'abstract', array(
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(3, 1024))
            ),
            'label' => 'Abstract:',
            'rows' => '10',
            'cols' => '60'
        ));


        $this->addElement('submit', 'submit', array(
            'required' => false,
            'ignore' => false,
            'label' => 'Submit',
            'decorators' => $this->_buttonDecorators
        ));


        $this->addElement('submit', 'cancel', array(
            'label' => 'Cancel',
            'required' => false,
            'decorators' => $this->_buttonDecorators
        ));

        $this->addTableGroup(array('firstname', 'lastname', 'email', 'institute', 'position'), true, 'personal', 'Personal Data');
        $this->addTableGroup(array('arrival', 'departure', 'hotel'), true, 'travel', 'Travel Information');
        $this->addTableGroup(array('presentationflag', 'title', 'abstract'), 'presentation', true, 'Presentation');
        $this->addButtonsGroup(array('submit', 'cancel'));
    }

    public function getDays() {
        return $this->_days;
    }

    public function getPositions() {
        return $this->_positions;
    }

}

