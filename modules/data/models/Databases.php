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

class Data_Model_Databases extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Databases');
    }

    /**
     * Returns all database entries.
     * @return array $response
     */
    public function index() {
        $databases = array();
        foreach($this->getResource()->fetchRows() as $row) {
            $database = $this->getResource()->fetchRow($row['id'], true, true);

            $database['publication_role'] = Daiquiri_Auth::getInstance()->getRole($database['publication_role_id']);
            foreach ($database['tables'] as &$table) {
                $table['publication_role'] = Daiquiri_Auth::getInstance()->getRole($table['publication_role_id']);
            }

            $databases[] = $database;
        }

        return array('databases' => $databases, 'status' => 'ok');
    }

    /**
     * Creates database entry.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        $form = new Data_Form_Database(array(
            'roles' => $roles,
            'submit' => 'Create database entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // get autofill flag
                $autofill = null;
                if (array_key_exists('autofill', $values)) {
                    $autofill = $values['autofill'];
                    unset($values['autofill']);
                }

                // check if entry is already there
                if ($this->getResource()->fetchId($values['name']) !== false) {
                    throw new Exception("Database entry already exists.");
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                // store the values in the database
                $databaseId = $this->getResource()->insertRow($values, $autofill);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a database entry.
     * @param mixed $input int id or array with "db" key
     * @return array $response
     */
    public function show($input, $fullData = false) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $id = $this->getResource()->fetchId($input['db']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Updates a database entry.
     * @param mixed $input int id or array with "db" key
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $id = $this->getResource()->fetchId($input['db']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        $form = new Data_Form_Database(array(
            'entry' => $entry,
            'roles' => $roles,
            'submit' => 'Update database entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                $this->getResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a database entry.
     * @param mixed $input int id or array with "db" key
     * @return array $response
     */
    public function delete($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $id = $this->getResource()->fetchId($input['db']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }
}
