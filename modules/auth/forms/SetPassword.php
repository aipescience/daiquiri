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

class Auth_Form_SetPassword extends Auth_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addNewPasswordElement('span6');
        $this->addConfirmPasswordElement('span6');
        $this->addSubmitButtonElement('submit', 'Set new password');
        $this->addButtonElement('cancel', 'Cancel');

        // set decorators
        $this->addHorizontalGroup(array('new_password', 'confirm_password'));
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));
    }
}
