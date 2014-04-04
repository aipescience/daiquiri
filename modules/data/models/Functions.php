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

class Data_Model_Functions extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Functions');
    }

    /**
     * Returns all function entries.
     * @return array $response
     */
    public function index() {
        $functions = array();

        foreach($this->getResource()->fetchRows() as $row) {
            $function = $this->getResource()->fetchRow($row['id']);
            $function['publication_role'] = Daiquiri_Auth::getInstance()->getRole($function['publication_role_id']);

            $functions[] = $function;
        }
        return array('functions' => $functions, 'status' => 'ok');
    }

    /**
     * Creates function entry.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Functions(array(
            'roles' => $roles,
            'submit' => 'Create function entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if entry is already there
                if ($this->getResource()->fetchIdByName($values['name']) !== false) {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form,'Function entry already exists.');
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                // store the values in the database
                $function_id = $this->getResource()->insertRow($values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a function entry.
     * @param mixed $input int id or array with "function" key
     * @return array $response
     */
    public function show($input) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['function'])) {
                throw new Exception('Either int id or array with "function" key must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['function']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        $data = $this->getResource()->fetchRow($id);
        
        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Updates a function entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['function'])) {
                throw new Exception('Either int id or array with "function" key must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['function']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get roles
        $roles = $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Functions(array(
            'entry' => $entry,
            'roles' => $roles,
            'submit' => 'Update table entry'
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
     * Deletes a function entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function delete($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['function'])) {
                throw new Exception('Either int id or array with "function" key must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['function']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }
}
