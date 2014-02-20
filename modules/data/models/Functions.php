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

class Data_Model_Functions extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Functions');
        $this->setValueField('name');
    }

    /**
     * Returns a lis of all database entries.
     * @return array
     */
    public function index() {
        $functions = array();
        foreach(array_keys($this->getValues()) as $id) {
            $response = $this->show($id);
            if ($response['status'] == 'ok') {
                $function = $response['data'];

                $function['publication_role'] = Daiquiri_Auth::getInstance()->getRole($function['publication_role_id']);

                $functions[] = $function;
            }
        }
        return $functions;
    }

    /**
     * Creates function entry.
     * @param array $formParams
     * @return array
     */
    public function create(array $formParams = array()) {
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Function(array(
                    'roles' => $roles,
                    'submit' => 'Create function entry'
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

                // check if the order needs to be set to NULL
                if ($values['order'] === '0' || $values['order'] === '') {
                    $values['order'] = NULL;
                }

                // store the values in the database
                $function_id = $this->getResource()->insertRow($values);

                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                if (empty($csrf)) {
                    return array('status' => 'error', 'form' => $form, 'errors' => $form->getMessages());
                } else {
                    $csrf->initCsrfToken();
                    return array('status' => 'error', 'errors' => $form->getMessages(), 'csrf' => $csrf->getHash());
                }
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a function entry.
     * @param int $id
     * @return array
     */
    public function show($id, $function = false) {
        // process input
        if ($id === false) {
            if ($function === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($function);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        $data = $this->getResource()->fetchRow($id);
        


        if (empty($data)) {
            return array('status' => 'error');
        } else {
            return array('status' => 'ok', 'data' => $data);
        }
    }

    public function update($id, $function = false, array $formParams = array()) {
        // process input
        if ($id === false) {
            if ($function === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($function);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get roles
        $roles = $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Function(array(
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
                if ($values['order'] === '0' || $values['order'] === '') {
                    $values['order'] = NULL;
                }

                $this->getResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                if (empty($csrf)) {
                    return array('status' => 'error', 'form' => $form, 'errors' => $form->getMessages());
                } else {
                    $csrf->initCsrfToken();
                    return array('status' => 'error', 'errors' => $form->getMessages(), 'csrf' => $csrf->getHash());
                }
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, $function = false, array $formParams = array()) {
        // process input
        if ($id === false) {
            if ($function === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($function);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        // create the form object
        $form = new Data_Form_Delete(array(
                    'submit' => 'Delete function entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // delete table row
                $this->getResource()->deleteRow($id);
                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                if (empty($csrf)) {
                    return array('status' => 'error', 'form' => $form, 'errors' => $form->getMessages());
                } else {
                    $csrf->initCsrfToken();
                    return array('status' => 'error', 'errors' => $form->getMessages(), 'csrf' => $csrf->getHash());
                }
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Checks whether the user can access this function
     * @param int $id
     * @return array
     */
    public function checkACL($id) {
        return $this->getResource()->checkACL($id);
    }

}
