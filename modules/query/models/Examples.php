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
        $this->setResource('Query_Model_Resource_Examples');
    }

    /**
     * Returns all configuration entries as an array.
     * @return array
     */
    public function index() {
        return $this->getResource()->fetchRows();
    }

    /**
     * Creates Example.
     * @param string $key
     * @param string $value
     * @return array
     */
    public function create(array $formParams = array()) {

        // get roles from rolesmodel
        $rolesModel = new Auth_Model_Roles();
        $roles = array_merge(array(0 => 'not published'), $rolesModel->getValues());

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
        $example = $this->getResource()->fetchRow($id);

        if ($example === null) {
            return array('status' => 'error', 'error' => 'id not found');
        }

        // get roles from rolesmodel
        $rolesModel = new Auth_Model_Roles();
        $roles = array_merge(array(0 => 'not published'), $rolesModel->getValues());

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
