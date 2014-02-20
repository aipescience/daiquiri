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

class Data_Model_Databases extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Databases');
        $this->setValueField('name');
    }

    /**
     * Returns a lis of all database entries.
     * @return array
     */
    public function index($fullData = false) {
        $databases = array();

        foreach($this->getResource()->fetchRows() as $row) {
            $id = $row['id'];
            $response = $this->show($id, false, $fullData);
            if ($response['status'] == 'ok') {
                $database = $response['data'];

                $database['publication_role'] = Daiquiri_Auth::getInstance()->getRole($database['publication_role_id']);

                foreach ($database['tables'] as &$table) {
                    $table['publication_role'] = Daiquiri_Auth::getInstance()->getRole($table['publication_role_id']);
                }

                $databases[] = $database;
            }
        }
        return $databases;
    }

    /**
     * Creates database entry.
     * @param array $formParams
     * @return array
     */
    public function create(array $formParams = array()) {

        // create the form object
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        $adapter = Daiquiri_Config::getInstance()->getDbAdapter();

        $form = new Data_Form_Database(array(
                    'roles' => $roles,
                    'adapter' => $adapter,
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

                // resolve database adapter index
                if (is_int($values['adapter'])) {
                    $values['adapter'] = $adapter[$values['adapter']];
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
                $databaseId = $this->getResource()->insertRow($values);

                if ($autofill) {
                    // get the additional resources
                    $descResource = new Data_Model_Resource_Description();
                    $tableModel = new Data_Model_Tables();

                    // auto create entries for all tables
                    try {
                        foreach ($descResource->fetchTables($values['name']) as $table) {

                            $t = $descResource->describeTable($values['name'], $table);

                            $tableDescription = $t;
                            $t['database_id'] = $databaseId;
                            $t['publication_role_id'] = $values['publication_role_id'];
                            $t['publication_select'] = $values['publication_select'];
                            $t['publication_update'] = $values['publication_update'];
                            $t['publication_insert'] = $values['publication_insert'];
                            unset($t['columns']);
                            unset($t['database']);

                            $t['autofill'] = $autofill;

                            $tableModel->store($t, $tableDescription);
                        }
                    } catch (Exception $e) {
                        $this->getResource()->deleteDatabase($databaseId);
                        throw $e;
                    }
                }

                return array('status' => 'ok');
            } else {
                return array('status' => 'error', 'errors' => $form->getMessages());
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a database entry.
     * @param int $id
     * @param string $db, the name of the database only used if $id === false
     * @param bool $fullData if tables and columns should be retrieved as well
     * @return array
     */
    public function show($id, $db = false, $fullData = false) {
        if ($id === false) {
            if ($db === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($db);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        // get database from database
        $database = $this->getResource()->fetchRow($id, $fullData);

        return array('status' => 'ok', 'data' => $database);
    }

    public function update($id, $db, array $formParams = array()) {
        if ($id === false) {
            if ($db === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($db);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        $adapter = Daiquiri_Config::getInstance()->getDbAdapter();

        $entry['adapter'] = array_search($entry['adapter'], $adapter);

        $form = new Data_Form_Database(array(
                    'entry' => $entry,
                    'roles' => $roles,
                    'adapter' => $adapter,
                    'submit' => 'Update database entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // resolve database adapter index
                $values['adapter'] = $adapter[$values['adapter']];

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                $this->getResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                $csrf->initCsrfToken();
                return array('status' => 'error', 'errors' => $form->getMessages(), 'csrf' => $csrf->getHash());
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, $db, array $formParams = array()) {
        if ($id === false) {
            if ($db === false) {
                throw new Exception('Either $id or $db must be provided.');
            }
            $id = $this->getResource()->fetchId($db);

            if (empty($id)) {
                return array('status' => 'error');
            }
        }

        // create the form object
        $form = new Data_Form_Delete(array(
            'submit' => 'Delete database entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)){
            if ($form->isValid($formParams)) {
                $this->getResource()->deleteDatabase($id);
                return array('status' => 'ok');
            } else {
                $csrf = $form->getElement('csrf');
                $csrf->initCsrfToken();
                return array('status' => 'error', 'errors' => $form->getMessages(), 'csrf' => $csrf->getHash());
            } 
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Checks whether the user can access this database
     * @param int $id
     * @param string $command SQL command
     * @return array
     */
    public function checkACL($id, $command) {
        return $this->getResource()->checkACL($id, $command);
    }

}
