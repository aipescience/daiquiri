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

class Query_Form_Mail extends Daiquiri_Form_Abstract {

    /**
     * The user entry for the user credential fields.
     * @var array
     */
    protected $_user = array();

    /**
     * The sql query to be send.
     * @var array
     */
    protected $_sql = array();

    /**
     * The query plan to be send.
     * @var array
     */
    protected $_plan = array();

    /**
     * Sets $_user.
     * @param array $user the user entry for the user credential fields
     */
    public function setUser($user) {
        $this->_user = $user;
    }

    /**
     * Sets $_sql.
     * @param [type] $sql the sql query to be send
     */
    public function setSql($sql) {
        $this->_sql = $sql;
    }

    /**
     * Sets $_plan.
     * @param [type] $plan the query plan to be send
     */
    public function setPlan($plan) {
        $this->_plan = $plan;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addTextareaElement('sql', array(
            'filters' => array('StringTrim'),
            'class' => 'mono input-xxlarge',
            'label' => 'Original query<br/><span class="hint">(not editable)</span><br/>',
            'rows' => '5',
            'ignore' => true
        ));
        $this->addTextareaElement('plan', array(
            'filters' => array('StringTrim'),
            'class' => 'mono input-xxlarge',
            'label' => 'Query plan<br/><span class="hint">(not editable)</span>',
            'rows' => '15',
            'ignore' => true
        ));
        $this->addTextareaElement('message', array(
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
        $this->addTextElement('firstname', array(
            'label' => 'First Name',
            'size' => '30',
            'required' => true,
            'filters' => array('StringTrim'),
            'required' => false,
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addTextElement('lastname', array(
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
        $this->addTextElement('email', array(
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
        $this->addSubmitButtonElement('submit', 'Send message');
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('sql', 'plan'), 'query-group', 'Query');
        $this->addHorizontalGroup(array('message'), 'message-group', 'Message (not required)');
        $this->addHorizontalGroup(array('firstname', 'lastname', 'email'), 'name-group', 'Sender (not required)');

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

        $this->setDefault('sql', $this->_sql);
        $this->setFieldReadonly('sql');

        $this->setDefault('plan', $this->_plan);
        $this->setFieldReadonly('plan');
    }

}
