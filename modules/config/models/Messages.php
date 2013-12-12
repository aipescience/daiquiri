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

class Config_Model_Messages extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_KeyValue');
        $this->getResource()->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getResource()->getTable()->setName('Config_Messages');
    }

    /**
     * Returns all status messages as an array.
     * @return array
     */
    public function index() {
        return $this->getResource()->fetchRows();
    }

    /**
     * Returns a particular status message.
     * @return array
     */
    public function show($key) {
        return $this->getResource()->fetchValue($key);
    }

    /**
     * Creates a status message.
     * @param string $key
     * @param string $value
     * @return array
     */
    public function create(array $formParams = array()) {

        // create the form object
        $form = new Config_Form_CreateMessages();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                if ($this->getResource()->fetchValue($values['key']) !== null) {
                    $form->setDescription('Key already stored');
                    return array('status' => 'error', 'error' => 'key already stored');
                } else {
                    // store the details
                    $this->getResource()->storeValue($values['key'], $values['value']);
                    return array('status' => 'ok');
                }
            } else {
                return array('form' => $form, 'status' => 'error', 'errors' => $form->getMessages());
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Edit an status message.
     * @param array $formParams
     * @return array
     */
    public function update($key, array $formParams = array()) {
        $value = $this->getResource()->fetchValue($key);

        if ($value === null) {
            return array('status' => 'error', 'error' => 'key not found');
        }

        // create the form object
        $form = new Config_Form_EditMessages(array(
                    'key' => $key,
                    'value' => $value
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            $this->getResource()->updateValue($key, $values['value']);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes an status message.
     * @param string $key
     * @param array $formParams
     * @return Array 
     */
    public function delete($key, array $formParams = array()) {
        // create the form object
        $form = new Config_Form_DeleteMessages();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteValue($key);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
