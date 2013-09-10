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
 * Model for the rule management for acl.
 */
class Auth_Model_Details extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Details');
    }

    /**
     * Returns a user detail.
     * @param int $id
     * @param string $key
     * @return array
     */
    public function show($id, $key) {
        // check if the detail is already there
        $detail = $this->getResource()->fetchValue($id, $key);
        if ($detail === null) {
            return array('status' => 'key not found');
        } else {
            return array('status' => 'ok', 'data' => $detail);
        }
    }

    /**
     * Creates a user detail.
     * @param int $id
     * @param string $key
     * @param string $value
     * @return array
     */
    public function create($id, array $formParams = array()) {
        // check for id
        if ($id === null) {
            throw new Exception('User id not given');
        }

        // create the form object
        $form = new Auth_Form_Detail();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                if ($this->getResource()->fetchValue($id, $values['key']) !== null) {
                    $form->setDescription('User detail already stored');
                    return array(
                        'status' => 'error',
                        'error' => 'user detail already stored',
                        'form' => $form
                    );
                } else {
                    // store the details
                    $this->getResource()->storeValue($id, $values['key'], $values['value']);
                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'form' => $form,
                    'status' => 'error',
                    'error' => $form->getMessages(),
                    'form' => $form
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Creates a user detail.
     * @param int $id
     * @param string $key
     * @param string $value
     * @return array
     */
    public function update($id, $key, array $formParams = array()) {
        // check for id
        if ($id === null) {
            throw new Exception('User id not given');
        }

        // check if the key is there
        $value = $this->getResource()->fetchValue($id, $key);
        if ($value === null) {
            return array('status' => 'error', 'error' => 'key not found');
        }

        // create the form object
        $form = new Auth_Form_Detail(array(
                    'key' => $key,
                    'value' => $value
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            $this->getResource()->updateValue($id, $key, $values['value']);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a user detail.
     * @param int $id
     * @param string $key
     * @return array
     */
    public function delete($id, $key, array $formParams = array()) {

        // check if the key is there
        if ($this->getResource()->fetchValue($id, $key) === null) {
            return array('status' => 'error', 'error' => 'key not found');
        }

        // create the form object
        $form = new Auth_Form_DeleteDetail();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            // check if the detail is protected
            if (in_array($key, Daiquiri_Config::getInstance()->auth->details->toArray())) {
                return array('status' => 'error', 'error' => 'key is protected');
            } else {
                $this->getResource()->deleteValue($id, $key);
                return array('status' => 'ok');
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
