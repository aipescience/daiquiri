<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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

class Auth_Form_ForgotPassword extends Auth_Form_Abstract {

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add form elements
        $this->addEmailElement(true);

        $this->addPrimaryButtonElement('submit', 'Request password reset');
        $this->addButtonElement('cancel', 'Cancel');
        $this->addCaptchaElement();

        // set decorators
        $this->addHorizontalGroup(array('email'));
        $this->addCaptchaGroup('captcha');
        $this->addActionGroup(array('submit', 'cancel'));
    }

}
