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

class Auth_Form_UpdateUser extends Auth_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $details = array();
        foreach ($this->getDetailKeys() as $detailKey) {
            $details[] = $this->addDetailElement($detailKey,'span6');
        }
        $elements = array();
        if ($this->_changeUsername) {
            $elements[] = $this->addUsernameElement('span6',$this->_user['id']);
        }
        if ($this->_changeEmail) {
            $elements[] = $this->addEmailElement('span6',$this->_user['id']);
        }
        $elements[] = $this->addRoleIdElement();
        $elements[] = $this->addStatusIdElement();

        $this->addSubmitButtonElement('submit', 'Update user');
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge($details,$elements));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach ($elements as $element) {
            if (isset($this->_user[$element])) {
                $this->setDefault($element, $this->_user[$element]);
            }
        }
        foreach ($this->getDetailKeys() as $detailKey) {
            $key = $detailKey['key'];

            if (isset($this->_user['details'][$key])) {
                if (in_array(Auth_Model_DetailKeys::$types[$detailKey['type_id']], array('checkbox','multiselect'))) {
                    $value = Zend_Json::decode($this->_user['details'][$key]);
                } else {
                    $value = $this->_user['details'][$key];
                }

                $this->setDefault($key,$value);
            }
        }
    }
}
