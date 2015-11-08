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

class Daiquiri_Model_Helper_Mail extends Daiquiri_Model_Helper_Abstract {

    public function template($template) {
        // get the template
        $templateModel = new Core_Model_Templates();
        return $templateModel->show($template);
    }

    public function send($template, array $values = array()) {
        // create a new mail
        $mail = new Zend_Mail('UTF-8');

        if (isset($values['to'])) {
            if (is_array($values['to'])) {
                foreach ($values['to'] as $address) {
                    $mail->addTo($address);
                }
            } else {
                $mail->addTo($values['to']);
            }
            unset($values['to']);
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
            unset($values['cc']);
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
            unset($values['bcc']);
        }

        // get the template
        $templateModel = new Core_Model_Templates();
        $data = $templateModel->show($template, $values);

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