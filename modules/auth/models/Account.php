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
 * Model for the user management.
 */
class Auth_Model_Account extends Daiquiri_Model_Abstract {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Returns the credentials of the user whic is currently logged in.
     * @return array 
     */
    public function show() {
        $id = Daiquiri_Auth::getInstance()->getCurrentId();
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Edits the credentials of the currently logged in user.
     * @param array $formParams
     * @return array
     */
    public function update(array $formParams = array()) {
        // get id
        $id = Daiquiri_Auth::getInstance()->getCurrentId();

        // create the form object
        $form = new Auth_Form_Edit(array(
                    'user' => $this->getResource()->fetchRow($id),
                    'details' => Daiquiri_Config::getInstance()->auth->details->toArray(),
                    'changeUsername' => Daiquiri_Config::getInstance()->auth->changeUsername,
                    'changeEmail' => Daiquiri_Config::getInstance()->auth->changeEmail,
                ));

        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            // update the user and redirect
            $this->getResource()->updateUser($id, $values);

            // log the event
            $detailsResource = new Auth_Model_Resource_Details();
            $detailsResource->logEvent($id, 'update by user');

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }
}
