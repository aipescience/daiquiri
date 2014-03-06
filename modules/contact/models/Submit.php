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

class Contact_Model_Submit extends Daiquiri_Model_Abstract {

    public function __construct() {
        $this->setResource('Contact_Model_Resource_Messages');
    }

    public function contact(array $formParams = array()) {

        // get categories
        $categoriesModel = new Contact_Model_Categories();
        $categories = $categoriesModel->getResource()->fetchValues('category');

        // get user if one is logged in
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        if ($userId > 0) {
            // get the user model for getting user details
            $userModel = new Auth_Model_User();
            $user = $userModel->show($userId);
        } else {
            $user = array();
        }

        // create the form object
        $form = new Contact_Form_Submit(array(
            'categories' => $categories,
            'user' => $user)
        );

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get values
                $values = $form->getValues();
                unset($values['submit']);

                // set the user_id
                $values['user_id'] = $userId;

                // set timestamp
                $values['datetime'] = date("Y-m-d H:i:s");

                // set status of new message to active
                $statusModel = new Contact_Model_Status();
                $values['status_id'] = $statusModel->getResource()->fetchId(array(
                    'where' => array('`status` = "active"')
                ));

                // store in database (if enabled)
                $this->getResource()->insertRow($values);

                // get the category
                $row = $categoriesModel->getResource()->fetchRow($values['category_id']);
                $values['category'] = $row['category'];

                // send mail to user who used the contact form
                $this->getModelHelper('mail')->send('contact.submit_user', array(
                    'to' => $values['email'],
                    'firstname' => $values['firstname'],
                    'lastname' => $values['lastname']
                ));

                // send mail to support
                $userMailModel = new Auth_Model_Mail();
                $this->getModelHelper('mail')->send('contact.submit_support', array(
                    'to' => array_merge(
                        $userMailModel->show('support'),
                        $userMailModel->show('manager'),
                        $userMailModel->show('admin')
                    ),
                    'reply_to' => $values['email'],
                    'firstname' => $values['firstname'],
                    'lastname' => $values['lastname'],
                    'email' => $values['email'],
                    'category' => $values['category'],
                    'subject' => $values['subject'],
                    'message' => $values['message'],
                    'link' => Daiquiri_Config::getInstance()->getSiteUrl() . '/contact/messages'
                ));

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'form' => $form
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}