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

class Auth_Model_Password extends Daiquiri_Model_Abstract {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Returns the (hashed) password (of a given type) of a given user from the database.
     * @param int $userId id of the user
     * @param string $type type of the password
     * @return array $response
     */
    public function show($userId, $type = 'default') {
        $password = $this->getResource()->fetchPassword($userId, $type);
        if (empty($password)) {
            throw new Daiquiri_Exception_NotFound();
        } else {
            return array('status' => 'ok', 'data' => $password);
        }
    }

    /**
     * Sends the email in the 'forgotten password workflow'.
     * @param array $formParams
     * @return array $response
     */
    public function forgot(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_ForgotPassword();

        // check if request is POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // get the user credentials
                $user = $this->getResource()->fetchRow(array('where' => array(
                    'email = ?' => $values['email']
                )));

                if ($user !== false) {
                    // produce random validation link
                    $code = $this->createRandomString(32);
                    // store code
                    $resource = new Auth_Model_Resource_Details();
                    $resource->deleteValue($user['id'], 'code');
                    $resource->insertValue($user['id'], 'code', $code);

                    // send mail
                    $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/password/reset/id/' . $user['id'] . '/code/' . $code;
                    $this->getModelHelper('mail')->send('auth.forgotPassword', array(
                        'to' => $values['email'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'link' => $link
                    ));
                }
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Resets the password in the 'forgotten password workflow'.
     * @param int $userId id of the user
     * @param string $code 
     * @param array $formParams
     * @return array $response
     */
    public function reset($userId, $code, array $formParams = array()) {
        // get the user
        $user = $this->getResource()->fetchRow($userId);

        // check if the code is ok
        if ($user !== false && !empty($user['code']) && $code === $user['code']) {
            // create the form object
            $form = new Auth_Form_ResetPassword();

            // check if request is POST
            if (!empty($formParams)) {
                if ($form->isValid($formParams)) {

                    // get the form values
                    $values = $form->getValues();

                    // check if the given username was correct
                    if ($values['username'] === $user['username']) {
                        // update the user
                        $this->getResource()->updatePassword($userId, $values['newPassword']);

                        // remove code
                        $resource = new Auth_Model_Resource_Details();
                        $resource->deleteValue($userId, 'code');

                        // log the event
                        $resource->logEvent($userId, 'resetPassword');

                        if (Daiquiri_Config::getInstance()->auth->disableOnForgotPassword) {
                            // disable user
                            $statusId = Daiquiri_Auth::getInstance()->getStatusId('disabled');
                            $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                            // send mail to admins
                            $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/user';
                            $this->getModelHelper('mail')->send('auth.resetPassword', array(
                                'to' => $this->getResource()->fetchEmailByRole('admin'),
                                'firstname' => $user['firstname'],
                                'lastname' => $user['lastname'],
                                'link' => $link
                            ));

                            return array('status' => 'ok', 'disable' => true);
                        } else {
                            return array('status' => 'ok', 'disable' => false);
                        }

                    } else {
                        $form->setDescription('The username is not correct.');
                        return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                    }

                } else {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                }
            }

            return array('form' => $form, 'status' => 'form');

        } else {
            return array('status' => 'error');
        }
    }

    /**
     * Resets the password of a given user to a new value.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function set($userId, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_SetPassword();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // update the user and redirect
                $this->getResource()->updatePassword($userId, $values['newPassword']);

                // log the event
                $resource = new Auth_Model_Resource_Details();
                $resource->logEvent($userId, 'setPassword');

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Edits the password of the currenly logged in user.
     * @param array $formParams
     * @return array $response
     */
    public function change(array $formParams = array()) {

        // get the id of the user from the request
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // create the form object
        $form = new Auth_Form_ChangePassword();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // check if the old password is valid
                $result = Daiquiri_Auth::getInstance()->authenticateUser($user['username'], $values['oldPassword']);

                if ($result) {
                    // update the user and redirect
                    $this->getResource()->updatePassword($userId, $values['newPassword']);

                    // log the event
                    $detailsResource = new Auth_Model_Resource_Details();
                    $detailsResource->logEvent($userId, 'changePassword');

                    return array('status' => 'ok');
                } else {
                    $form->setDescription('Wrong (old) password provided');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                }

            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
