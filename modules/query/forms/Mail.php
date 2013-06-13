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

class Query_Form_Mail extends Daiquiri_Form_Abstract {

    protected $_user = array();
    protected $_sql = array();
    protected $_plan = array();

    public function setUser($user) {
        $this->_user = $user;
    }

    public function setSql($sql) {
        $this->_sql = $sql;
    }

    public function setPlan($plan) {
        $this->_plan = $plan;
    }

    /**
     * @brief Initializes the contact form.
     */
    public function init() {
        parent::init();

        // add elements
        $this->addElement('textarea', 'sql', array(
            'filters' => array('StringTrim'),
            'class' => 'mono input-xxlarge',
            'label' => 'Original query<br/><span class="hint">(not editable)</span><br/>',
            'rows' => '5',
            'ignore' => true
        ));
        $this->addElement('textarea', 'plan', array(
            'filters' => array('StringTrim'),
            'class' => 'mono input-xxlarge',
            'label' => 'Query plan<br/><span class="hint">(not editable)</span>',
            'rows' => '15',
            'ignore' => true
        ));
        $this->addElement('textarea', 'message', array(
            'label' => 'Message',
            'class' => 'input-xxlarge',
            'rows' => '10',
            'required' => true,
            'required' => false,
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(3, 1024)),
                array('validator' => new Daiquiri_Form_Validator_Textarea())
            )
        ));
        $this->addElement('text', 'firstname', array(
            'label' => 'First Name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'required' => false,
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'lastname', array(
            'label' => 'Last Name',
            //'description' => 'Please enter your last name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'required' => false,
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'email', array(
            'label' => 'Email',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'required' => false,
            'validators' => array(
                array('validator' => 'EmailAddress'),
            )
        ));
        if (empty($this->_user)) {
            // display captcha if no user is logged in
            $this->addCaptchaElement();
        }
        $this->addPrimaryButtonElement('submit', 'Send message');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('sql', 'plan'), 'query-group', 'Query');
        $this->addHorizontalGroup(array('message'), 'message-group', 'Message (not required)');
        $this->addHorizontalGroup(array('firstname', 'lastname', 'email'), 'name-group', 'Sender (not required)');

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

        $this->setDefault('sql', $this->_sql);
        $this->setFieldReadonly('sql');

        $this->setDefault('plan', $this->_plan);
        $this->setFieldReadonly('plan');
    }

}
