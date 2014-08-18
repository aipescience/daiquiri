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

class Auth_Form_CreateUser extends Auth_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        foreach ($this->getDetails() as $detail) {
            $this->addDetailElement($detail);
        }
        $elements = array(
            $this->addUsernameElement(),
            $this->addEmailElement(),
            $this->addNewPasswordElement(),
            $this->addConfirmPasswordElement(),
            $this->addRoleIdElement(),
            $this->addStatusIdElement()
        );
        $this->addPrimaryButtonElement('submit', 'Create user');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup($this->getDetails(), 'detail-group');
        $this->addHorizontalGroup($elements, 'user-group');
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));
    }

}
