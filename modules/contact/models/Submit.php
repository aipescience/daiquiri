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

class Contact_Model_Submit extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Contact_Model_Resource_Messages');
    }

    /**
     * Submits a contact message.
     * @param array $formParams
     * @return array $response
     */
    public function contact(array $formParams = array()) {
        // get categories
        $categoriesModel = new Contact_Model_Categories();
        $categories = $categoriesModel->getResource()->fetchValues('category');

        // get user if one is logged in
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        if ($userId > 0) {
            // get the user model for getting user details
            $userModel = new Auth_Model_User();
            $user = $userModel->getResource()->fetchRow($userId);
        } else {
            $user = array();
        }

        // create the form object
        $form = new Contact_Form_Submit(array(
            'categories' => $categories,
            'user' => $user
        ));

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
                $userResource = new Auth_Model_Resource_User();
                $this->getModelHelper('mail')->send('contact.submit_support', array(
                    'to' => array_merge(
                        $userResource->fetchEmailByRole('support'),
                        $userResource->fetchEmailByRole('manager'),
                        $userResource->fetchEmailByRole('admin')
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