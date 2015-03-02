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

                    // log the event
                    Daiquiri_Log::getInstance()->notice("password reset requested by user '{$user['username']}'");

                    // send mail
                    $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/password/reset/id/' . $user['id'] . '/code/' . $code;
                    $this->getModelHelper('mail')->send('auth.forgotPassword', array(
                        'to' => $values['email'],
                        'firstname' => $user['details']['firstname'],
                        'lastname' => $user['details']['lastname'],
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
        if ($user !== false && !empty($user['details']['code']) && $code === $user['details']['code']) {
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
                        $this->getResource()->updatePassword($userId, $values['new_password']);

                        // remove code
                        $resource = new Auth_Model_Resource_Details();
                        $resource->deleteValue($userId, 'code');

                        // log the event
                        Daiquiri_Log::getInstance()->notice("password reset by user '{$user['username']}'");

                        // send a notification mail to the admins
                        if (Daiquiri_Config::getInstance()->auth->notification->changePassword) {
                            $this->getModelHelper('mail')->send('auth.changePassword', array(
                                'to' => Daiquiri_Config::getInstance()->auth->notification->mail->toArray(),
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'firstname' => $user['details']['firstname'],
                                'lastname' => $user['details']['lastname']
                            ));
                        }

                        return array('status' => 'ok');

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
                $this->getResource()->updatePassword($userId, $values['new_password']);

                // log the event
                Daiquiri_Log::getInstance()->notice("password set by admin (user_id: {$userId})");

                // send a notification mail
                if (Daiquiri_Config::getInstance()->auth->notification->changePassword) {
                    $user = $this->getResource()->fetchRow($userId);
                    $this->getModelHelper('mail')->send('auth.changePassword', array(
                        'to' => Daiquiri_Config::getInstance()->auth->notification->mail->toArray(),
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'firstname' => $user['details']['firstname'],
                        'lastname' => $user['details']['lastname']
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
                $result = Daiquiri_Auth::getInstance()->authenticateUser($user['username'], $values['old_password']);

                if ($result) {
                    if ($values['new_password'] !== $values['old_password']) {
                        // update the user and redirect
                        $this->getResource()->updatePassword($userId, $values['new_password']);

                        // log the event
                        Daiquiri_Log::getInstance()->notice('password changed by user');

                        // send a notification mail
                        if (Daiquiri_Config::getInstance()->auth->notification->changePassword) {
                            $this->getModelHelper('mail')->send('auth.changePassword', array(
                                'to' => Daiquiri_Config::getInstance()->auth->notification->mail->toArray(),
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'firstname' => $user['details']['firstname'],
                                'lastname' => $user['details']['lastname']
                            ));
                        }
                    }
                    return array('status' => 'ok');
                } else {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form, 'Wrong (old) password provided');
                }

            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
