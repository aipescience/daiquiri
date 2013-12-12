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

class Query_Model_Examples extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getResource()->getTable()->setName('Query_Examples');
    }

    /**
     * Returns all configuration entries as an array.
     * @return array
     */
    public function index() {
        $examples = array();
        foreach ($this->getResource()->fetchRows() as $example) {
            // check for permission to access
            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($example['publication_role_id'])) {
                $example['publication_role'] = Daiquiri_Auth::getInstance()->getRole($example['publication_role_id']);
                unset($example['publication_role_id']);
                $examples[] = $example;
            }
        }
        return $examples;
    }

    /**
     * Creates Example.
     * @param string $key
     * @param string $value
     * @return array
     */
    public function create(array $formParams = array()) {
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Query_Form_CreateExample(array(
            'roles' => $roles
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                $this->getResource()->insertRow($values);
                return array('status' => 'ok');

            } else {
                return array(
                    'form' => $form,
                    'status' => 'error',
                    'errors' => $form->getMessages());
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Edit an entry in the config table.
     * @param array $formParams
     * @return array
     */
    public function update($id, array $formParams = array()) {
        // get example from database
        $example = $this->getResource()->fetchRow($id);
        if ($example === null) {
            return array('status' => 'error', 'error' => 'id not found');
        }

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Query_Form_UpdateExample(array(
            'example' => $example,
            'roles' => $roles
        ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            $this->getResource()->updateRow($id, $values);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes an config entry.
     * @param string $key
     * @param array $formParams
     * @return Array 
     */
    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Query_Form_DeleteExample();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteRow($id);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
