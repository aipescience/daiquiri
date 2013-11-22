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
 * Model for registration of new users and other operations which involve a
 * change of the status of a user. This involves registration, validation, 
 * confirmation, rejection, activation, disabling, and reenabling.
 */
class Auth_Model_Registration extends Daiquiri_Model_PaginatedTable {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Registers a new user.
     * @param array $formParams
     * @return Object
     */
    public function register(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Register(array(
                    'details' => Daiquiri_Config::getInstance()->auth->details->toArray()
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();
            unset($values['confirmPassword']);

            // produce random validation link
            $values['code'] = $this->createRandomString(32);

            // (pre-) log the event
            $date = date("Y-m-d\TH:i:s");
            $ip = Daiquiri_Auth::getInstance()->getRemoteAddr();
            $user = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $values['register'] = 'date:' . $date . ',ip:' . $ip . ',user:' . $user;

            // create the user and return
            $id = $this->getResource()->registerUser($values);
            unset($values['newPassword']);

            // send mail
            $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/registration/validate/id/' . $id . '/code/' . $values['code'];
            $mailResource = new Auth_Model_Resource_Mail();
            $mailResource->sendRegisterMail($values, array('link' => $link));

            return array('status' => 'ok');
        }
        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Validates a new user via link.
     * @param int $id
     * @return Object
     */
    public function validate($id, $code) {
        // validate user by its code
        $user = $this->getResource()->validateUser($id, $code);

        // return with the apropriate string
        if ($user) {
            // log the event
            $resource = new Auth_Model_Resource_Details();
            $resource->logEvent($user['id'], 'validate');

            if (Daiquiri_Config::getInstance()->auth->activation) {
                // send mail since the user needs to be activated/confirmed
                $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/user';
                $mailResource = new Auth_Model_Resource_Mail();
                $mailResource->sendValidateMail($user, array('link' => $link));
            } else {
                // get the new status id
                $statusModel = new Auth_Model_Status();
                $statusId = $statusModel->getId('active');

                // confirm user in database
                $this->getResource()->updateUser($user['id'], array('status_id' => $statusId));

                // log the event
                $detailResource = new Auth_Model_Resource_Details();
                $detailResource->logEvent($user['id'], 'activate');

                // send mail
                $mailResource = new Auth_Model_Resource_Mail();
                $mailResource->sendActivateMail($user);
            }

            return array('status' => 'ok');
        } else {
            return array(
                'status' => 'error',
                'error' => 'user or code is not valid'
            );
        }
    }

    /**
     * Sets the status of a registered user to 'confirmed'.
     * @param int $id
     */
    public function confirm($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Confirm();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // update the user
                if ($user['status'] !== 'registered') {
                    return array(
                        'status' => 'error',
                        'error' => 'user status is not "registered"'
                    );
                } else {
                    // get the new status id
                    $statusModel = new Auth_Model_Status();
                    $statusId = $statusModel->getId('confirmed');

                    // confirm user in database
                    $this->getResource()->updateUser($id, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($id, 'confirm');

                    // send mail
                    $mailResource = new Auth_Model_Resource_Mail();
                    $mailResource->sendConfirmMail($user);

                    return array('status' => 'ok');
                }        
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a registrered user to 'disabled'.
     * @param int $id
     */
    public function reject($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Reject();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // update the user
                if ($user['status'] !== 'registered') {
                    return array(
                        'status' => 'error',
                        'error' => 'user status is not "registered"'
                    );
                } else {
                    // get the new status id
                    $statusModel = new Auth_Model_Status();
                    $statusId = $statusModel->getId('disabled');

                    // confirm user in database
                    $this->getResource()->updateUser($id, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($id, 'reject');

                    // send mail
                    $mailResource = new Auth_Model_Resource_Mail();
                    $mailResource->sendRejectMail($user);

                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user from 'confirmed' to 'active'.
     * @param int $id
     */
    public function activate($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Activate();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // update the use
                if ($user['status'] === 'active') {
                    return array(
                        'status' => 'error',
                        'error' => 'user status is already "active"'
                    );
                } else {
                    // get the new status id
                    $statusModel = new Auth_Model_Status();
                    $statusId = $statusModel->getId('active');

                    // confirm user in database
                    $this->getResource()->updateUser($id, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($id, 'activate');

                    // send mail
                    $mailResource = new Auth_Model_Resource_Mail();
                    $mailResource->sendActivateMail($user);

                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user to 'disabled'.
     * @param int $id
     */
    public function disable($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Disable();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // update the user
                if ($user['status'] === 'disabled') {
                    return array(
                        'status' => 'error',
                        'error' => 'user status is already "disabled"'
                    );
                } else {
                    // get the new status id
                    $statusModel = new Auth_Model_Status();
                    $statusId = $statusModel->getId('disabled');
                    Zend_Debug::dump($statusId); // die(0);
                    // confirm user in database
                    $this->getResource()->updateUser($id, array('status_id' => $statusId));

                    // invalidate the session of the user
                    $sessionResource = new Auth_Model_Resource_Sessions();
                    foreach ($sessionResource->fetchAuthSessionsByUserId($id) as $session) {
                        $sessionResource->deleteRow($session);
                    };

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($id, 'disable');

                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user from 'disabled' to 'active'.
     * @param int $id
     */
    public function reenable($id, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Reenable();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($id);

                // update the use
                if ($user['status'] === 'active') {
                    return array(
                        'status' => 'error',
                        'error' => 'user status is already "active"'
                    );
                } else {
                    // get the new status id
                    $statusModel = new Auth_Model_Status();
                    $statusId = $statusModel->getId('active');

                    // confirm user in database
                    $this->getResource()->updateUser($id, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($id, 'reenable');

                    // send mail
                    $mailResource = new Auth_Model_Resource_Mail();
                    $mailResource->sendReenableMail($user);

                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
