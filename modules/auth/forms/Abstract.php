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
     * Array which holds the detail keys.
     * @var array 
     */
    protected $_detailKeys = array();

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
     * @param array $detailKeys 
     */
    public function setDetailKeys($detailKeys) {
        $this->_detailKeys = $detailKeys;
    }

    /**
     * Returns the detail array.
     * @return array
     */
    public function getDetailKeys() {
        return $this->_detailKeys;
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
     * @param  array  $detailKey description of the user detail
     * @return string $name      name of the element
     */
    public function addDetailElement($detailKey, $class = '') {

        switch (Auth_Model_DetailKeys::$types[$detailKey['type_id']]) {
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
                    'required' => $detailKey['required'],
                    'multiOptions' => Zend_Json::decode($detailKey['options'])
                ));
                break;
            case "select":
                $this->addSelectElement($detailKey['key'], array(
                    'label' => ucfirst(str_replace('_',' ', $detailKey['key'])),
                    'hint' => $detailKey['hint'],
                    'required' => $detailKey['required'],
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
                    'required' => $detailKey['required'],
                    'filters' => array('StringTrim'),
                    'validators' => array(
                        array('validator' => new Daiquiri_Form_Validator_Text()),
                    )
                ));
        }

        return $detailKey['key'];
    }

    /**
     * Creates a form element for the username and adds it to the form.
     * @param int $excludeId exclude a certain user id from the unique-ness validator
     * @return string $name   name of the element
     */
    public function addUsernameElement($class = '', $excludeId = null) {
        $this->addElement(new Auth_Form_Element_Username(array(
            'excludeId' => $excludeId,
            'class' => $class
        )));
        return 'username';
    }

    /**
     * Creates a form element for the appname and adds it to the form.
     * @param int $excludeId exclude a certain user id from the unique-ness validator
     * @return string $name   name of the element
     */
    public function addAppnameElement($class = '', $excludeId = null) {
        $this->addElement(new Auth_Form_Element_Appname(array(
            'excludeId' => $excludeId,
            'class' => $class
        )));
        return 'appname';
    }

    /**
     * Creates a form element for the email and adds it to the form.
     * @param int $excludeId exclude a certain user id from the unique-ness validator
     * @return string $name   name of the element
     */
    public function addEmailElement($class = '', $excludeId = null) {
        $this->addElement(new Auth_Form_Element_Email(array(
            'excludeId' => $excludeId,
            'class' => $class
        )));
        return 'email';
    }

    /**
     * Creates a form element for the old password and adds it to the form.
     * @return string $name name of the element
     */
    public function addOldPasswordElement($class = '') {
        $this->addElement(new Auth_Form_Element_OldPassword(array(
            'class' => $class
        )));
        return 'old_password';
    }

    /**
     * Creates a form element for the new password and adds it to the form.
     * @return string $name name of the element
     */
    public function addNewPasswordElement($class = '') {
        $this->addElement(new Auth_Form_Element_NewPassword(array(
            'class' => $class
        )));
        return 'new_password';
    }

    /**
     * Creates a form element for the password confirmation and adds it to the form.
     * @return string $name name of the element 
     */
    public function addConfirmPasswordElement($class = '') {
        $this->addElement(new Auth_Form_Element_ConfirmPassword(array(
            'class' => $class
        )));
        return 'confirm_password';
    }

    /**
     * Creates a form element for the role id and adds it to the form.
     * @return string $name name of the element
     */
    public function addRoleIdElement($class = '') {
        $this->addElement(new Auth_Form_Element_RoleId(array(
            'multiOptions' => $this->_roles,
            'class' => $class
        )));
        return 'role_id';
    }

    /**
     * Creates a form element for the status id and adds it to the form.
     * @return string $name name of the element
     */
    public function addStatusIdElement($class = '') {
        $this->addElement(new Auth_Form_Element_StatusId(array(
            'multiOptions' => $this->_status,
            'class' => $class
        )));
        return 'status_id';
    }

}
