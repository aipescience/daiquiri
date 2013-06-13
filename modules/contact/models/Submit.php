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
 * Provides methods for everything related to contact form
 */
class Contact_Model_Submit extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getResource()->getTable()->setName('Contact_Messages');
    }

    /**
     * Displaying and processes the contact form.
     * @return array
     */
    public function contact(array $formParams = array()) {

        // get categories and user model
        $categoriesModel = new Contact_Model_Categories();
        $userModel = new Auth_Model_User();

        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        if ($userId > 0) {
            // get the user model for getting user details
            $user = $userModel->show($userId);
        } else {
            $user = array();
        }

        // create the form object
        $form = new Contact_Form_Submit(array(
                    'categories' => $categoriesModel->getValues(),
                    'user' => $user)
        );

        if (!empty($formParams) && $form->isValid($formParams)) {

            // form is valid, get values
            $values = $form->getValues();
            unset($values['submit']);

            // get username of current user
            $values['user_id'] = Daiquiri_Auth::getInstance()->getCurrentId();

            // set status of new message to active
            $statusModel = new Contact_Model_Status();
            $values['status_id'] = $statusModel->getId('active');

            // store in database (if enabled)
            $this->getResource()->insertRow($values);

            // get the category
            $values['category'] = $categoriesModel->getValue($values['category_id']);

            // send mail to user who used the contact form
            $mailResourceUser = new Contact_Model_Resource_Mail();
            $mailResourceUser->sendSubmitMailToUser($values);

            // send mail to support
            $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/contact/messages';
            $mailResourceSupport = new Contact_Model_Resource_Mail();
            $mailResourceSupport->sendSubmitMailToSupport($values, array('link' => $link));

            return array('form' => null, 'status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}