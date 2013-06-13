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

class Auth_Model_Resource_Mail extends Daiquiri_Model_Resource_Mail {

    /**
     * Send a confirmation mail in case of a forgotten password.
     * @param array $values 
     */
    public function sendForgotPasswordMail(array $values, array $meta) {
        // set address
        $this->_mail->addTo($values['email']);

        // set mail and send
        $this->_setSubjectAndBodyText('auth.forgotPassword', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a mail to the admins that a forgotten password was reset.
     * @param array $values 
     */
    public function sendResetPasswordMail(array $values, array $meta) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addCc($model->show('admin'));

        // set mail and send
        $this->_setSubjectAndBodyText('auth.resetPassword', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a confirmation mail for a new user registration.
     * @param array $values 
     */
    public function sendRegisterMail(array $values, array $meta) {
        // set address
        $this->_mail->addTo($values['email']);

        // set mail and send
        $this->_setSubjectAndBodyText('auth.register', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a mail to the managers/admins that a registration was validated.
     * @param array $values 
     */
    public function sendValidateMail(array $values, array $meta) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($model->show('manager'));
        $this->_mail->addCc($model->show('admin'));

        // set mail and send
        $this->_setSubjectAndBodyText('auth.validate', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a mail to the managers/admins that a user needs to be confirmed.
     * @param array $values 
     */
    public function sendConfirmMail(array $values) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($model->show('manager'));
        $this->_mail->addCc($model->show('admin'));

        // set mail and send
        $meta = array('manager' => Daiquiri_Auth::getInstance()->getCurrentUsername());
        $this->_setSubjectAndBodyText('auth.confirm', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a mail to the managers/admins that a user needs to be activated.
     * @param array $values 
     */
    public function sendRejectMail(array $values) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($model->show('manager'));
        $this->_mail->addCc($model->show('admin'));

        // set mail and send
        $meta = array('manager' => Daiquiri_Auth::getInstance()->getCurrentUsername());
        $this->_setSubjectAndBodyText('auth.reject', $this->_filterValues($values, $meta));
        $this->_send();
    }

    /**
     * Send a mail to a user that he/she has been activated.
     * @param array $values 
     */
    public function sendActivateMail(array $values) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($values['email']);
        $this->_mail->addBcc($model->show('manager'));
        $this->_mail->addBcc($model->show('admin'));

        // set mail and send
        $this->_setSubjectAndBodyText('auth.activate', $this->_filterValues($values));
        $this->_send();
    }

    /**
     * Send a mail to a user that he/she has been re-enabled.
     * @param array $values 
     */
    public function sendReenableMail(array $values) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($values['email']);
        $this->_mail->addBcc($model->show('manager'));
        $this->_mail->addBcc($model->show('admin'));

        // set mail and send
        $this->_setSubjectAndBodyText('auth.reenable', $this->_filterValues($values));
        $this->_send();
    }

    /**
     * Filters values for sending mails.
     * @param array $values
     * @param array $meta
     * @return array 
     */
    private function _filterValues(array $values, array $meta = array()) {
        $details = Daiquiri_Config::getInstance()->auth->details->toArray();
        $keys = array_flip(array_merge(array('username', 'email'), $details));
        $filteredValues = array_intersect_key($values, $keys);
        return array_merge($filteredValues, $meta);
    }

}
