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

class Auth_Model_Login extends Daiquiri_Model_Abstract {

    /**
     * Authenticates a given user.
     * @param array $formParams
     * @return $response
     */
    public function login(array $formParams = array()) {

        // get the form object
        $form = new Auth_Form_Login();

        // check if request is POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get values
                $values = $form->getValues();

                // create DbAuth model and authenticate
                $result = Daiquiri_Auth::getInstance()->authenticateUser($values['username'], $values['password'], $values['remember']);

                // redirect depending on result of authentication
                if ($result) {
                    return array('status' => 'redirect');
                } else {
                    $form->setDescription('Wrong credentials provided');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Destroys the session of the user currently logged in.
     * @param boot $cms whether to log out of the cms as well
     * @return array $response
     */
    public function logout($cms = true) {
        $cookies = array();

        // get the auth singleton, clear the identity and redirect.
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::forgetMe();

        return array('status' => 'redirect', 'cookies' => $cookies);
    }

}
