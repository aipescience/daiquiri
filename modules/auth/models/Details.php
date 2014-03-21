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

class Auth_Model_Details extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Details');
    }

    /**
     * Returns a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @return array $response
     */
    public function show($userId, $key) {
        $detail = $this->getResource()->fetchValue($userId, $key);
        if ($detail === false) {
            return array('status' => 'key not found');
        } else {
            return array('status' => 'ok', 'data' => $detail);
        }
    }

    /**
     * Creates a user detail.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function create($userId, array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Details(array(
            'submit' => 'Create detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                if ($this->getResource()->fetchValue($userId, $values['key']) === false) {
                    // store the details
                    $this->getResource()->insertValue($userId, $values['key'], $values['value']);
                    return array('status' => 'ok');
                } else {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param array $formParams
     * @return array $response
     */
    public function update($userId, $key, array $formParams = array()) {
        // get the detail from the database
        $value = $this->getResource()->fetchValue($userId, $key);
        if ($value === false) {
            return array('status' => 'error', 'error' => 'Key not found');
        }

        // create the form object
        $form = new Auth_Form_Details(array(
            'key' => $key,
            'value' => $value,
            'submit' => 'Update detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $this->getResource()->updateValue($userId, $key, $values['value']);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param array $formParams
     * @return array $response
     */
    public function delete($userId, $key, array $formParams = array()) {
        // check if the key is there
        if ($this->getResource()->fetchValue($userId, $key) === false) {
            return array('status' => 'error', 'error' => 'Key not found');
        } else if (in_array($key, Daiquiri_Config::getInstance()->auth->details->toArray())) {
            return array('status' => 'error', 'error' => 'Key is protected');
        }

        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Delete detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $this->getResource()->deleteValue($userId, $key);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
