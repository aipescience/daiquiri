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

class Contact_Form_Submit extends Daiquiri_Form_Abstract {

    protected $_categories = array();
    protected $_user = array();

    public function setCategories($categories) {
        $this->_categories = $categories;
    }

    public function setUser($user) {
        $this->_user = $user;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
        $this->addElement('text', 'firstname', array(
            'label' => 'Your first name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'lastname', array(
            'label' => 'Your last name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'email', array(
            'label' => 'Your email address',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'EmailAddress'),
            )
        ));
        $this->addElement('select', 'category_id', array(
            'label' => 'Category',
            'multiOptions' => $this->_categories,
            'cols' => '30',
            'required' => true
        ));
        $this->addElement('text', 'subject', array(
            'label' => 'Subject',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 128)),
                array('validator' => new Daiquiri_Form_Validator_Text())
            )
        ));
        $this->addElement('textarea', 'message', array(
            'label' => 'Message<br/><span class="hint">(max. 2048<br/>characters)',
            'class' => 'input-xxlarge',
            'rows' => '10',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 2048)),
                array('validator' => new Daiquiri_Form_Validator_Textarea())
            )
        ));
        if (empty($this->_user)) {
            // display captcha if no user is logged in
            $this->addElement(new Daiquiri_Form_Element_Captcha('captcha'));
        }
        $this->addPrimaryButtonElement('submit', 'Send message');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('firstname', 'lastname', 'email'), 'name-group');
        $this->addHorizontalGroup(array('category_id', 'subject', 'message'), 'detail-group');
        if (empty($this->_user)) {
            $this->addCaptchaGroup('captcha');
        }
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields if user is logged in.
        foreach (array('firstname', 'lastname', 'email') as $key) {
            if (isset($this->_user[$key])) {
                $this->setDefault($key, $this->_user[$key]);
                $this->setFieldReadonly($key);
            }
        }
    }

}
