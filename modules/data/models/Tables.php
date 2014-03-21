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

class Data_Model_Tables extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Tables');
    }

    /**
     * Creates table entry.
     * @param array $formParams
     * @return array $reponse
     */
    public function create($databaseId = null, array $formParams = array()) {
        // get databases
        $databasesModel = new Data_Model_Databases();
        $databases = $databasesModel->getResource()->fetchValues('name');

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Tables(array(
            'databases' => $databases,
            'databaseId' => $databaseId,
            'roles' => $roles,
            'submit' => 'Create table entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $values = $form->getValues();

                // get autofill flag
                $autofill = null;
                if (array_key_exists('autofill', $values)) {
                    $autofill = $values['autofill'];
                    unset($values['autofill']);
                }

                // check if entry is already there
                $database = $databases[$values['database_id']];
                if ($this->getResource()->fetchId($database, $values['name']) !== false) {
                    throw new Exception("Table entry already exists.");
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                $this->getResource()->insertRow($values, $autofill);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a table entry.
     * @param mixed $input int id or array with "db" and "table" keys
     * @return array $response
     */
    public function show($input, $fullData = false) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table'])) {
                throw new Exception('Either int id or array with "db" and "table" keys must be provided as $input');
            }
            $id = $this->getResource()->fetchId($input['db'],$input['table']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        $data = $this->getResource()->fetchRow($id, $fullData);

        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Updates a table entry.
     * @param mixed $input int id or array with "db" and "table" keys
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table'])) {
                throw new Exception('Either $id or $db and $table must be provided.');
            }
            $id = $this->getResource()->fetchId($input['db'],$input['table']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get databases
        $databasesModel = new Data_Model_Databases();
        $databases = $databasesModel->getResource()->fetchValues('name');

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        $form = new Data_Form_Tables(array(
            'databases' => $databases,
            'databaseId' => $entry['database_id'],
            'roles' => $roles,
            'submit' => 'Update table entry',
            'entry' => $entry
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
     * Deletes a table entry.
     * @param mixed $input int id or array with "db" and "table" keys
     * @return array $response
     */
    public function delete($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table'])) {
                throw new Exception('Either int id or array with "db" and "table" keys must be provided as $input');
            }
            $id = $this->getResource()->fetchId($input['db'],$input['table']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }
}
