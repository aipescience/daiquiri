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
 * @brief Abstract base class for all mail resources in the daiquiri framework
 */
abstract class Daiquiri_Model_Resource_Mail extends Daiquiri_Model_Resource_Abstract {

    /**
     * Mail object
     * @var Zend_Mail
     */
    protected $_mail;

    /**
     * Constructor. Sets encoding.
     */
    public function __construct() {
        $this->_mail = new Zend_Mail('UTF-8');
    }

    /**
     * Sets the subject and the body of the mail from templates
     * @param string $context
     * @param array $values 
     */
    protected function _setSubjectAndBodyText($template, array $values = array()) {
        // get template from template model
        $templateModel = new Config_Model_Templates();
        $data = $templateModel->show($template, $values);

        // set subject and body of mail
        $this->_mail->setSubject($data['subject']);
        $this->_mail->setBodyText($data['body']);
    }

    protected function _send() {
        if (Daiquiri_Config::getInstance()->mail
                && Daiquiri_Config::getInstance()->mail->debug) {
            Zend_Debug::dump($this->_mail->getRecipients());
            Zend_Debug::dump($this->_mail->getSubject());
            Zend_Debug::dump($this->_mail->getBodyText());
            die(0);
        } else {
            $this->_mail->send();
        }
    }

}