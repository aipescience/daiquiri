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
 * Model for operations on the password(s) of a user.
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
     * @return array 
     */
    public function show($id, $type = 'default') {
        return $this->getResource()->fetchPassword($id, $type);
    }

    /**
     * Sends the email in the 'forgotten password workflow'.
     * @param array $formParams
     * @return Object
     */
    public function forgot(array $formParams = array()) {

        // create the form object
        $form = new Auth_Form_ForgotPassword();

        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // get the user credentials
            $where = $this->getResource()->getTable()->getAdapter()->quoteInto('`email` = ?', $values['email']);
            $rows = $this->getResource()->fetchRows(array('where' => array($where)));

            if ($rows) {
                // get user from the rowset
                $user = $rows[0];

                // produce random validation link
                $code = $this->createRandomString(32);

                // store code
                $resource = new Auth_Model_Resource_Details();
                $oldcode = $resource->fetchValue($user['id'], 'code');
                if ($oldcode) {
                    $resource->deleteValue($user['id'], 'code');
                }
                $resource->storeValue($user['id'], 'code', $code);

                // send mail
                $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/password/reset/id/' . $user['id'] . '/code/' . $code;
                $mailResource = new Auth_Model_Resource_Mail();
                $mailResource->sendForgotPasswordMail($user, array('link' => $link));
            }
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Resets the password in the 'forgotten password workflow'.
     * @param int $id
     * @param array $formParams
     * @return Object
     */
    public function reset($id, $code, array $formParams = array()) {

        // get the user for the id
        $user = $this->getResource()->fetchRow($id);

        // check if the code is ok
        if (!empty($user['code']) && $code === $user['code']) {
            // create the form object
            $form = new Auth_Form_ResetPassword();

            // valiadate the form if POST
            if (!empty($formParams) && $form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // check if the given username was correct
                if ($values['username'] === $user['username']) {
                    // update the user
                    $this->getResource()->storePassword($id, $values['newPassword']);

                    // remove code
                    $resource = new Auth_Model_Resource_Details();
                    $resource->deleteValue($id, 'code');

                    // log the event
                    $resource->logEvent($id, 'resetPassword');

                    if (Daiquiri_Config::getInstance()->auth->disableOnForgotPassword) {
                        // disable user
                        $statusModel = new Auth_Model_Status();
                        $statusId = $statusModel->getId('disabled');
                        $this->getResource()->updateUser($id, array('status_id' => $statusId));

                        // send mail to admins
                        $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/user';
                        $mailResource = new Auth_Model_Resource_Mail();
                        $mailResource->sendResetPasswordMail($user, array('link' => $link));

                        return array('status' => 'ok', 'disable' => true);
                    } else {
                        return array('status' => 'ok', 'disable' => false);
                    }
                } else {
                    $form->setDescription('The username is not correct.');
                }
            }
            return array('form' => $form, 'status' => 'form');
        } else {
            return array('status' => 'error');
        }
    }

    /**
     * Resets the password of a given user to a new value.
     * @param int $id
     * @param array $formParams
     * @return Object
     */
    public function set($id, array $formParams = array()) {

        // create the form object
        $form = new Auth_Form_SetPassword();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // update the user and redirect
                $this->getResource()->storePassword($id, $values['newPassword']);

                // log the event
                $resource = new Auth_Model_Resource_Details();
                $resource->logEvent($id, 'setPassword');

                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                $csrf->initCsrfToken();
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'form' => $form,
                    'csrf' => $csrf->getHash()
                    );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Edits the password of the currenly logged in user.
     * @param array $formParams
     * @return Object
     */
    public function change(array $formParams = array()) {

        // get the id of the user from the request
        $id = Daiquiri_Auth::getInstance()->getCurrentId();

        // create the form object
        $form = new Auth_Form_ChangePassword();

        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // get the user credentials
            $user = $this->getResource()->fetchRow($id);

            // create DbAuth model check if the old password is valid
            $result = Daiquiri_Auth::getInstance()->authenticateUser($user['username'], $values['oldPassword']);

            if ($result) {
                // update the user and redirect
                $this->getResource()->storePassword($id, $values['newPassword']);

                // log the event
                $detailsResource = new Auth_Model_Resource_Details();
                $detailsResource->logEvent($id, 'changePassword');

                return array('status' => 'ok');
            } else {
                $form->setDescription('Wrong (old) password provided');
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
