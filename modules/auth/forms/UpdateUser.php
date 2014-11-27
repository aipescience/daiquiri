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

class Auth_Form_UpdateUser extends Auth_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();
        
        // add elements
        $details = array();
        foreach ($this->getDetailKeys() as $detailKey) {
            $details[] = $this->addDetailElement($detailKey);
        }
        $elements = array();
        if ($this->_changeUsername) {
            $elements[] = $this->addUsernameElement($this->_user['id']);
        }
        if ($this->_changeEmail) {
            $elements[] = $this->addEmailElement($this->_user['id']);
        }
        $elements[] = $this->addRoleIdElement();
        $elements[] = $this->addStatusIdElement();
        $this->addPrimaryButtonElement('submit', 'Update user profile');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array_merge($details,$elements));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));

        // set fields
        foreach ($elements as $element) {
            if (isset($this->_user[$element])) {
                $this->setDefault($element, $this->_user[$element]);
            }
        }
        foreach ($this->getDetailKeys() as $detailKey) {
            if (isset($this->_user[$detailKey['key']])) {
                $key = $detailKey['key'];

                if (in_array(Auth_Model_DetailKeys::$types[$detailKey['type_id']], array('checkbox','multiselect'))) {
                    $value = Zend_Json::decode($this->_user[$key]);
                } else {
                    $value = $this->_user[$key];
                }

                $this->setDefault($key,$value);
            }
        }
    }
}
