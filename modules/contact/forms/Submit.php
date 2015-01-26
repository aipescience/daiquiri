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

class Contact_Form_Submit extends Daiquiri_Form_Abstract {

    /**
     * The set of categories to choose from
     * @var array
     */
    protected $_categories = array();

    /**
     * The user credentials for this form.
     * @var array
     */
    protected $_user = array();

    /**
     * Sets $_categories.
     * @param array $categories the set of categories to choose from
     */
    public function setCategories($categories) {
        $this->_categories = $categories;
    }

    /**
     * Sets $_user.
     * @param array $user the user credentials for this form
     */
    public function setUser($user) {
        $this->_user = $user;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addTextElement('firstname', array(
            'label' => 'Your first name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('lastname', array(
            'label' => 'Your last name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('email', array(
            'label' => 'Your email address',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'EmailAddress'),
            )
        ));
        $this->addSelectElement('category_id', array(
            'label' => 'Category',
            'multiOptions' => $this->_categories,
            'cols' => '30',
            'required' => true
        ));
        $this->addTextElement('subject', array(
            'label' => 'Subject',
            'class' => 'input-xxlarge',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 128)),
                array('validator' => new Daiquiri_Form_Validator_Text())
            )
        ));
        $this->addTextareaElement('message', array(
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
            $this->addCaptchaElement();
        }
        $this->addSubmitButtonElement('submit', 'Send message');
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('firstname', 'lastname', 'email'), 'name-group');
        $this->addHorizontalGroup(array('category_id', 'subject', 'message'), 'detail-group');
        if (empty($this->_user)) {
            $this->addHorizontalGroup(array('captcha'),'captcha-group');
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
