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
class Auth_Form_Element_ConfirmPassword extends Auth_Form_Element_AbstractPassword {

    /**
     * Construtor. Sets the name of the element.
     * @param array $options form options for this element
     */
    public function __construct($options = null) {
        parent::__construct('confirm_password', $options);
    }

    /**
     * Initializes the form element.
     */
    function init() {
        parent::init();

        // set label
        $this->setLabel('Confirm password');

        // add validataor for comparison with NewPassword element
        $newPassword = Zend_Controller_Front::getInstance()->getRequest()->getParam('new_password');
        $validator = new Zend_Validate_Identical($newPassword);
        $validator->setMessage("The passwords do not match.");
        $this->addValidator($validator);
    }
}
