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
 * Model for logging in and out.
 */
class Auth_Model_Login extends Daiquiri_Model_Abstract {

    /**
     * Authenticates a given user.
     * @param array $formParams
     * @return Array
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
                $result = Daiquiri_Auth::getInstance()->authenticateUser($values['username'], $values['password']);

                // redirect depending on result of authentication
                if ($result) {
                    $cookies = array();

                    if (Daiquiri_Config::getInstance()->cms->enabled) {
                        $model = new Cms_Model_Wordpress();
                        $cookies = $model->login($values['username'], $values['password']);
                    }

                    return array(
                        'status' => 'redirect',
                        'cookies' => $cookies
                    );
                } else {
                    $form->setDescription('Wrong credentials provided');
                }
            }
        }

        // log me out of wordpress for sanity
        if (Daiquiri_Config::getInstance()->cms->enabled) {
            $model = new Cms_Model_Wordpress();
            $cookies = $model->logout();
        }

        return array('form' => $form, 'status' => 'form', 'cookies' => $cookies);
    }

    /**
     * Detroys the session of the user currently logged in.
     * @param boot $cms whether to log out of the cms as well
     * @return Array
     */
    public function logout($cms = true) {
        $cookies = array();

        if ($cms === true && Daiquiri_Config::getInstance()->cms->enabled) {
            // logout from wordpress
            $model = new Cms_Model_Wordpress();
            $cookies = $model->logout();
        }

        // get the auth singleton, clear the identity and redirect.
        Zend_Auth::getInstance()->clearIdentity();

        return array(
            'status' => 'redirect',
            'cookies' => $cookies
        );
    }

}
