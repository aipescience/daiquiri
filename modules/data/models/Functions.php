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
 * Model for the currently running query jobs.
 */
class Data_Model_Functions extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Functions');
        $this->setValueField('name');
    }

    /**
     * Returns a list of all function entries.
     * @return array
     */
    public function index() {
        return $this->getValues();
    }

    /**
     * Returns the id of the function by name
     * @param string $name
     * @return array
     */
    public function fetchIdWithName($name) {
        return $this->getResource()->fetchIdWithName($name);
    }

    /**
     * Checks whether the user can access this function
     * @param int $id
     * @return array
     */
    public function checkACL($id) {
        return $this->getResource()->checkACL($id);
    }

    /**
     * Returns a function entry.
     * @param int $id
     * @return array
     */
    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Creates function entry.
     * @param array $formParams
     * @return array
     */
    public function create(array $formParams = array()) {
        // get databases model
        $databasesModel = new Data_Model_Databases();

        // get roles model
        $rolesModel = new Auth_Model_Roles();

        // create the form object
        $form = new Data_Form_Function(array(
                    'roles' => array_merge(array(0 => 'not published'), $rolesModel->getValues()),
                    'submit' => 'Create function entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            // get autofill flag
            $autofill = null;
            if (array_key_exists('autofill', $values)) {
                $autofill = $values['autofill'];
                unset($values['autofill']);
            }

            // store the values in the database
            $function_id = $this->getResource()->insertRow($values);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function update($id, array $formParams = array()) {
        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $rolesModel = new Auth_Model_Roles();

        $form = new Data_Form_Function(array(
                    'entry' => $entry,
                    'roles' => array_merge(array(0 => 'not published'), $rolesModel->getValues()),
                    'submit' => 'Update table entry'
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

    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Data_Form_Delete(array(
                    'submit' => 'Delete function entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            // delete table row
            $this->getResource()->deleteRow($id);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
