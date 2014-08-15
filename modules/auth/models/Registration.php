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

class Auth_Model_Registration extends Daiquiri_Model_Abstract {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Returns all registration entries.
     * @return array $response
     */
    public function index() {
        return array(
            'status' => 'ok',
            'rows' => $this->getResource()->fetchRegistrations()
        );
    }

    /**
     * Deletes a registration entry.
     * @param int $id id of registration entry
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Delete registration entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // delete the user and redirect
                $this->getResource()->deleteRegistration($id);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Registers a new user.
     * @param array $formParams
     * @return array $response
     */
    public function register(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Registration(array(
            'details' => Daiquiri_Config::getInstance()->auth->details->toArray()
        ));

        // check if request is POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();
                unset($values['confirm_password']);

                // produce random validation link
                $values['code'] = $this->createRandomString(32);

                // (pre-) log the event
                $date = date("Y-m-d\TH:i:s");
                $ip = Daiquiri_Auth::getInstance()->getRemoteAddr();
                $user = Daiquiri_Auth::getInstance()->getCurrentUsername();
                $values['register'] = 'date:' . $date . ',ip:' . $ip . ',user:' . $user;

                // create the user and return
                $userId = $this->getResource()->registerUser($values);
            
                // send mail
                $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/registration/validate/id/' . $userId . '/code/' . $values['code'];
                $this->getModelHelper('mail')->send('auth.register', array(
                    'to' => $values['email'],
                    'firstname' => $values['firstname'],
                    'lastname' => $values['lastname'],
                    'link' => $link
                ));

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }
        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Validates a new user via link.
     * @param int $userId id of the user
     * @param string $code
     * @return array $response
     */
    public function validate($userId, $code) {
        // validate user by its code
        $user = $this->getResource()->validateUser($userId, $code);

        // return with the apropriate string
        if ($user !== false) {
            // log the event
            $resource = new Auth_Model_Resource_Details();
            $resource->logEvent($user['id'], 'validate');

            if (Daiquiri_Config::getInstance()->auth->activation) {
                // send mail since the user needs to be activated/confirmed
                $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/auth/user';
                $manager = array_merge(
                    $this->getResource()->fetchEmailByRole('admin'),
                    $this->getResource()->fetchEmailByRole('manager')
                );
                $this->getModelHelper('mail')->send('auth.validate', array(
                    'to' => $manager,
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'link' => $link
                ));
                return array('status' => 'ok','pending' => true);

            } else {
                // get the new status id
                $statusId = Daiquiri_Auth::getInstance()->getStatusId('active');

                // activate user in database
                $this->getResource()->updateRow($user['id'], array('status_id' => $statusId));

                return array('status' => 'ok','pending' => false);
            }
        } else {
            return array(
                'status' => 'error',
                'error' => 'user or code is not valid'
            );
        }
    }

    /**
     * Sets the status of a registered user to 'confirmed'.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function confirm($userId, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Confirm user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // update the user
                if ($user['status'] !== 'registered') {
                    $form->setDescription('User status is not "registered"');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                } else {
                    // get the new status id
                    $statusId = Daiquiri_Auth::getInstance()->getStatusId('confirmed');

                    // confirm user in database
                    $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($userId, 'confirm');

                    // send mail
                    $this->getModelHelper('mail')->send('auth.confirm', array(
                        'to' => $this->getResource()->fetchEmailByRole('admin'),
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'username' => $user['username'],
                        'manager' => Daiquiri_Auth::getInstance()->getCurrentUsername()
                    ));

                    return array('status' => 'ok');
                }        
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a registrered user to 'disabled'.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function reject($userId, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Reject user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // update the user
                if ($user['status'] !== 'registered') {
                    $form->setDescription('User status is not "registered"');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                } else {
                    // get the new status id
                    $statusId = Daiquiri_Auth::getInstance()->getStatusId('disabled');

                    // disable user in database
                    $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($userId, 'reject');

                    // send mail
                    $manager = array_merge(
                        $this->getResource()->fetchEmailByRole('admin'),
                        $this->getResource()->fetchEmailByRole('manager')
                    );
                    $this->getModelHelper('mail')->send('auth.reject', array(
                        'to' => $manager,
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'username' => $user['username'],
                        'manager' => Daiquiri_Auth::getInstance()->getCurrentUsername()
                    ));

                    return array('status' => 'ok');
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user from 'confirmed' to 'active'.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function activate($userId, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Activate user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // update the use
                if ($user['status'] === 'active') {
                    $form->setDescription('User status is already "active"');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                } else {
                    // get the new status id
                    $statusId = Daiquiri_Auth::getInstance()->getStatusId('active');

                    // activate user in database
                    $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($userId, 'activate');

                    // send mail
                    $manager = array_merge(
                        $this->getResource()->fetchEmailByRole('admin'),
                        $this->getResource()->fetchEmailByRole('manager')
                    );
                    $this->getModelHelper('mail')->send('auth.activate', array(
                        'to' => $user['email'],
                        'bcc' => $manager,
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'username' => $user['username']
                    ));

                    return array('status' => 'ok');
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user to 'disabled'.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function disable($userId, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Disable user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // update the user
                if ($user['status'] === 'disabled') {
                    $form->setDescription('User status is already "disabled"');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                } else {
                    // get the new status id
                    $statusId = Daiquiri_Auth::getInstance()->getStatusId('disabled');

                    // disable user in database
                    $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                    // invalidate the session of the user
                    $sessionResource = new Auth_Model_Resource_Sessions();
                    foreach ($sessionResource->fetchAuthSessionsByUserId($userId) as $session) {
                        $sessionResource->deleteRow($session);
                    };

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($userId, 'disable');

                    return array('status' => 'ok');
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Sets the status of a given user from 'disabled' to 'active'.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function reenable($userId, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Reenable user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $user = $this->getResource()->fetchRow($userId);

                // update the use
                if ($user['status'] === 'active') {
                    $form->setDescription('User status is already "active"');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                } else {
                    // get the new status id
                    $statusId = Daiquiri_Auth::getInstance()->getStatusId('active');

                    // activate user in database
                    $this->getResource()->updateRow($userId, array('status_id' => $statusId));

                    // log the event
                    $detailResource = new Auth_Model_Resource_Details();
                    $detailResource->logEvent($userId, 'reenable');

                    // send mail
                    $this->getModelHelper('mail')->send('auth.reenable', array(
                        'to' => $user['email'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'username' => $user['username']
                    ));

                    return array('status' => 'ok');
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
