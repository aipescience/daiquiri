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

class Daiquiri_Model_Helper_Mail extends Daiquiri_Model_Helper_Abstract {

    public function template($template) {
        // get the template
        $templateModel = new Config_Model_Templates();
        return $templateModel->show($template);
    }

    public function send($template, array $values = array(), array $templateData = array()) {
        // create a new mail
        $mail = new Zend_Mail('UTF-8');

        // get the template
        $templateModel = new Config_Model_Templates();
        $data = $templateModel->show($template, $values, $templateData);

        if (isset($values['to'])) {
            if (is_array($values['to'])) {
                foreach ($values['to'] as $address) {
                    $mail->addTo($address);
                }
            } else {
                $mail->addTo($values['to']);
            }
        } else {
            throw new Exception('to not send in $values');
        }

        // set cc
        if (isset($values['cc'])) {
            if (is_array($values['cc'])) {
                foreach ($values['cc'] as $address) {
                    $mail->addCc($address);
                }
            } else {
                $mail->addCc($values['cc']);
            }
        }

        // set bcc
        if (isset($values['bcc'])) {
            if (is_array($values['bcc'])) {
                foreach ($values['bcc'] as $address) {
                    $mail->addBcc($address);
                }
            } else {
                $mail->addBcc($values['bcc']);
            }
        }

        // set subject and body
        $mail->setSubject($data['subject']);
        $mail->setBodyText($data['body']);

        if (empty(Daiquiri_Config::getInstance()->mail->debug)) {
            $mail->send();
        } else {
            Zend_Debug::dump($mail->getRecipients());
            Zend_Debug::dump($mail->getSubject());
            Zend_Debug::dump($mail->getBodyText());
        }
    }
}