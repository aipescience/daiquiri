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
 * Resource model for the mails send from the contact module.
 */
class Contact_Model_Resource_Mail extends Daiquiri_Model_Resource_Mail {

    /**
     * Sends a mail to the user containing the message text sent with 
     * the contact form.
     * @param array $values
     */
    public function sendSubmitMailToUser(array $values) {
        // set address
        $this->_mail->addTo($values['email']);

        // set mail and send
        $this->_setSubjectAndBodyText('contact.submit_user', $values);
        $this->_send();
    }

    /**
     * Sends a mail to the support that a new contact message was send.
     * @param array $values
     */
    public function sendSubmitMailToSupport(array $values, array $meta) {
        // get mail model
        $model = new Auth_Model_Mail();

        // set addresses
        $this->_mail->addTo($model->show('support'));
        $this->_mail->addTo($model->show('manager'));
        $this->_mail->addTo($model->show('admin'));
        $this->_mail->setReplyTo($values['email']);

        // set mail and send
        $this->_setSubjectAndBodyText('contact.submit_support', array_merge($values, $meta));
        $this->_send();
    }

    /**
     * Returns the template for the respond mail.
     * @param array $values
     * @return string
     */
    public function getRespondTemplate(array $values) {
        // get template from template model
        $templateModel = new Config_Model_Templates();
        return $templateModel->show('contact.respond', $values);
    }

    /**
     * Sends the respond mail to the user.
     * @param array $values
     * @param array $meta
     */
    public function sendRespondMail(array $values, array $meta) {
        // set addresses
        $this->_mail->addTo($meta['email']);
        $this->_mail->addCc(Daiquiri_Auth::getInstance()->getCurrentEmail());

        // set subject and body and send mail
        $this->_mail->setSubject($values['subject']);
        $this->_mail->setBodyText($values['body']);
        $this->_send();
    }

}

