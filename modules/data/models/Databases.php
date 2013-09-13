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
    public function index() {
        return $this->getValues();
    }

    public function getValues() {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // get rows from databse
        $rows = $this->getResource()->fetchRows(array('order' => 'id asc',
            'from' => array('id', $this->_valueField, 'database')));

        // convert to flat array and return
        $values = array();
        foreach ($rows as $row) {
            $values[$row['id']] = $row[$this->_valueField];
        }
        return $values;
    }

    /**
     * Returns the id of the database with the given name
     * @param string $name
     * @return array
     */
    public function fetchIdWithName($name) {
        return $this->getResource()->fetchIdWithName($name);
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

    /**
     * Returns a database entry.
     * @param int $id
     * @param bool $idIsName = true, if $id is actually a string containing the name of the database
     *             or $idIsName = false, if $id is the row id as saved in the database
     * @param bool $fullData if tables and columns should be retrieved as well...
     * @return array
     */
    public function show($id, $idIsName = false, $fullData = true) {
        if($idIsName === true) {
            $id = $this->getResource()->fetchIdWithName($id);

            if($id === false) {
                return false;
            }
        }
        return $this->getResource()->fetchRow($id, $fullData);
    }

    /**
     * Creates database entry.
     * @param array $formParams
     * @return array
     */
    public function create(array $formParams = array()) {

        // create the form object
        $rolesModel = new Auth_Model_Roles();
        $adapter = Daiquiri_Config::getInstance()->getDbAdapter();

        $form = new Data_Form_Database(array(
                    'roles' => array_merge(array(0 => 'not published'), $rolesModel->getValues()),
                    'adapter' => $adapter,
                    'submit' => 'Create database entry'
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

            // resolve database adapter index
            if (is_int($values['adapter'])) {
                $values['adapter'] = $adapter[$values['adapter']];
            }

            //check if entry is already there
            if ($this->getResource()->fetchIdWithName($values['name']) !== false) {
                throw new Exception("Database entry already exists.");
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
        $adapter = Daiquiri_Config::getInstance()->getDbAdapter();

        $entry['adapter'] = array_search($entry['adapter'], $adapter);

        $form = new Data_Form_Database(array(
                    'entry' => $entry,
                    'roles' => array_merge(array(0 => 'not published'), $rolesModel->getValues()),
                    'adapter' => $adapter,
                    'submit' => 'Update database entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            // resolve database adapter index
            $values['adapter'] = $adapter[$values['adapter']];

            $this->getResource()->updateRow($id, $values);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Data_Form_Delete(array(
                    'submit' => 'Delete database entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteDatabase($id);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
