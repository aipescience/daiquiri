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

class Auth_Form_ForgotPassword extends Auth_Form_Abstract {

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add form elements
        $this->addTextElement('email', array(
            'label' => 'Email',
            'required' => true,
            'filter' => 'StringTrim',
            'validators' => array(
                array('validator' => 'emailAddress'),
            )
        ));
        $this->addSubmitButtonElement('submit', 'Request password reset');
        $this->addCancelButtonElement('cancel', 'Cancel');
        $this->addCaptchaElement();

        // set decorators
        $this->addHorizontalGroup(array('email'),'email-group');
        $this->addHorizontalGroup(array('captcha'),'captcha-group');
        $this->addActionGroup(array('submit', 'cancel'));
    }

}
