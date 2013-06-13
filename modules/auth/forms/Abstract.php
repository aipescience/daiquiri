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
 * Abstract base class for all forms in Daiquiri_Auth 
 */
abstract class Auth_Form_Abstract extends Daiquiri_Form_Abstract {

    /**
     * Array which holds the credentials of the user to be edited.
     * @var array 
     */
    protected $_user = array();

    /**
     * Array which holds the different roles.
     * @var array 
     */
    protected $_roles = array();

    /**
     * Array which holds the differen status.
     * @var array 
     */
    protected $_status = array();

    /**
     * Array which holds the details of the user to be edited.
     * @var array 
     */
    protected $_details = array();

    /**
     * Switch if usernames can be changed.
     * @var bool
     */
    protected $_changeUsername = false;

    /**
     * Switch if email addresses can be changed.
     * @var bool
     */
    protected $_changeEmail = false;

    /**
     * Sets the user array.
     * @param array $user 
     */
    public function setUser($user) {
        $this->_user = $user;
    }

    /**
     * Sets the role array.
     * @param array $roles 
     */
    public function setRoles($roles) {
        $this->_roles = $roles;
    }

    /**
     * Sets the status array.
     * @param array $status 
     */
    public function setStatus($status) {
        $this->_status = $status;
    }

    /**
     * Sets the detail array.
     * @param array $details 
     */
    public function setDetails($details) {
        $this->_details = $details;
    }

    /**
     * Returns the detail array.
     * @return array
     */
    public function getDetails() {
        return $this->_details;
    }

    /**
     * Sets the changeUsername flag.
     */
    public function setChangeUsername($changeUsername) {
        return $this->_changeUsername = $changeUsername;
    }

    /**
     * Sets the changeEmail flag.
     */
    public function setChangeEmail($changeEmail) {
        return $this->_changeEmail = $changeEmail;
    }

    /**
     * Creates a form element for a detail and adds it to the form.
     * @param string $detail
     * @param bool $required
     * @return string 
     */
    public function addDetailElement($detail, $required = false) {
        // create form element
        $field = new Zend_Form_Element_Text($detail);
        $field->setLabel(ucfirst($detail));

        // add stuff
        $field->addFilter('StringTrim');
        $field->addValidator(new Daiquiri_Form_Validator_Text());
        $field->setRequired($required);

        $this->addElement($field);
        return $detail;
    }

    /**
     * Creates a form element for the username and adds it to the form.
     * @param bool $required
     * @param bool $unique
     * @return string 
     */
    public function addUsernameElement($required = false, $unique = false, $excludeId = null) {
        // create form element
        $field = new Zend_Form_Element_Text('username');
        $field->setLabel('Username');

        // add stuff
        $field->addFilter('StringTrim');
        if (Daiquiri_Config::getInstance()->auth->lowerCaseUsernames) {
            $field->addValidator(new Daiquiri_Form_Validator_LowerCaseAlnum());
        } else {
            $field->addValidator('alnum');
        }

        $minLength = Daiquiri_Config::getInstance()->auth->usernameMinLength;
        $field->addValidator('StringLength', false, array($minLength, 80));

        $field->setRequired($required);

        // add validator for beeing unique
        if ($unique) {
            $val = new Zend_Validate();
            $msg = 'The username is in use, please use another username.';

            $val1 = new Zend_Validate_Db_NoRecordExists('Auth_User', 'username');
            $val1->setMessage($msg);
            if ($excludeId) {
                $val1->setExclude(array(
                    'field' => 'id',
                    'value' => $excludeId
                ));
            }

            $val2 = new Zend_Validate_Db_NoRecordExists('Auth_Registration', 'username');
            $val2->setMessage($msg);

            $val3 = new Zend_Validate_Db_NoRecordExists('Auth_Apps', 'appname');
            $val3->setMessage($msg);

            $val->addValidator($val1)->addValidator($val2)->addValidator($val3);

            $field->addValidator($val);
        }

        $this->addElement($field);
        return 'username';
    }

    /**
     * Creates a form element for the appname and adds it to the form.
     * @param bool $required
     * @param bool $unique
     * @return string 
     */
    public function addAppnameElement($required = false, $unique = false, $excludeId = null) {
        // create form element
        $field = new Zend_Form_Element_Text('appname');
        $field->setLabel('Appname');

        // add stuff
        $field->addFilter('StringTrim');
        if (Daiquiri_Config::getInstance()->auth->lowerCaseUsernames) {
            $field->addValidator(new Daiquiri_Form_Validator_LowerCaseAlnum());
        } else {
            $field->addValidator('alnum');
        }

        $minLength = Daiquiri_Config::getInstance()->auth->usernameMinLength;
        $field->addValidator('StringLength', false, array($minLength, 80));

        $field->setRequired($required);

        // add validator for beeing unique
        if ($unique) {
            $val = new Zend_Validate();
            $msg = 'The username is in use, please use another username.';

            $val1 = new Zend_Validate_Db_NoRecordExists('Auth_User', 'username');
            $val1->setMessage($msg);
            if ($excludeId) {
                $val1->setExclude(array(
                    'field' => 'id',
                    'value' => $excludeId
                ));
            }

            $val2 = new Zend_Validate_Db_NoRecordExists('Auth_Registration', 'username');
            $val2->setMessage($msg);

            $val3 = new Zend_Validate_Db_NoRecordExists('Auth_Apps', 'appname');
            $val3->setMessage($msg);

            $val->addValidator($val1)->addValidator($val2)->addValidator($val3);

            $field->addValidator($val);
        }

        $this->addElement($field);
        return 'appname';
    }

    /**
     * Creates a form element for the email and adds it to the form.
     * @param bool $required
     * @param bool $unique
     * @return string 
     */
    public function addEmailElement($required = false, $unique = false, $excludeId = null) {
        // create form element
        $field = new Zend_Form_Element_Text('email');
        $field->setLabel('Email');

        // add stuff
        $field->addFilter('StringTrim');
        $field->addValidator('emailAddress');
        $field->setRequired($required);

        // add validator for beeing unique
        if ($unique) {
            $val = new Zend_Validate();
            $msg = 'The email is already in the database, please check if you are already registered.';
            $val1 = new Zend_Validate_Db_NoRecordExists('Auth_User', 'email');
            $val1->setMessage($msg);
            if ($excludeId) {
                $val1->setExclude(array(
                    'field' => 'id',
                    'value' => $excludeId
                ));
            }
            $val2 = new Zend_Validate_Db_NoRecordExists('Auth_Registration', 'email');
            $val2->setMessage($msg);
            $val->addValidator($val1)->addValidator($val2);

            $field->addValidator($val);
        }

        $this->addElement($field);
        return 'email';
    }

    /**
     * Creates a form element for the password and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addPasswordElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Password('password');
        $field->setLabel('Password');

        // add stuff
        $field->addFilter('StringTrim');
        $field->addValidator(new Daiquiri_Form_Validator_Text());
        $field->setRequired($required);

        $this->addElement($field);
        return 'password';
    }

    /**
     * Creates a form element for the old password and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addOldPasswordElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Password('oldPassword');
        $field->setLabel('Old Password');

        // add stuff
        $field->addFilter('StringTrim');
        $field->addValidator(new Daiquiri_Form_Validator_Text());
        $field->setRequired($required);

        $this->addElement($field);
        return 'oldPassword';
    }

    /**
     * Creates a form element for the new password and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addNewPasswordElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Password('newPassword');
        $field->setLabel('New Password');

        // add stuff
        $field->addFilter('StringTrim');
        $field->addValidator(new Daiquiri_Form_Validator_Text());
        $minLength = Daiquiri_Config::getInstance()->auth->passwordMinLength;
        $field->addValidator('StringLength', false, array($minLength, 80));
        $field->setRequired($required);

        $this->addElement($field);
        return 'newPassword';
    }

    /**
     * Creates a form element for the password confirmation and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addConfirmPasswordElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Password('confirmPassword');
        $field->setLabel('Confirm Password');

        // add stuff
        $field->addFilter('StringTrim');
        $field->setRequired($required);

        // add custom validataor
        $val = new Zend_Validate_Identical(
                        Zend_Controller_Front::getInstance()->getRequest()->getParam('newPassword'));
        $val->setMessage("The passwords do not match.");
        $field->addValidator($val);
        $this->addElement($field);
        return 'confirmPassword';
    }

    /**
     * Creates a form element for the role id and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addRoleIdElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Select('role_id');
        $field->setLabel('Role');

        // add stuff
        $field->addMultiOptions($this->_roles);
        $field->setRequired($required);

        $this->addElement($field);
        return 'role_id';
    }

    /**
     * Creates a form element for the status id and adds it to the form.
     * @param bool $required
     * @return string 
     */
    public function addStatusIdElement($required = false) {
        // create form element
        $field = new Zend_Form_Element_Select('status_id');
        $field->setLabel('Status');

        // add stuff
        $field->addMultiOptions($this->_status);
        $field->setRequired($required);

        $this->addElement($field);
        return 'status_id';
    }

}
