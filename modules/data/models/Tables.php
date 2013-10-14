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
class Data_Model_Tables extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Tables');
        $this->setValueField('name');
    }

    public function getValues() {
        // check if $_valueField is set
        if ($this->_valueField === null) {
            throw new Exception('$_valueField is not set in ' . __METHOD__);
        }

        // get rows from database
        $rows = $this->getResource()->fetchRows(array('order' => 'id asc',
            'from' => array('id', $this->_valueField, 'database')));

        // convert to flat array and return
        $values = array();
        foreach ($rows as $row) {
            $values[$row['id']] = $row['database'] . '.' . $row[$this->_valueField];
        }
        return $values;
    }

    /**
     * Returns the id of the table with the given name and given database id
     * @param int $dbId
     * @param string $name
     * @return array
     */
    public function fetchIdWithName($dbId, $name) {
        return $this->getResource()->fetchIdWithName($dbId, $name);
    }

    /**
     * Checks whether the user can access this table
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
     * @param str $tablename if set, $id is assumed to be the table name
     * @param bool $fullData if tables and columns should be retrieved as well...
     * @return array
     */
    public function show($id, $tablename = false, $fullData = true) {
        if($tablename !== false) {
            // get databases model
            $databasesModel = new Data_Model_Databases();
            $tableId = $databasesModel->getResource()->fetchIdWithName($tablename);
            $id = $this->getResource()->fetchIdWithName($tableId, $id);

            if ($id === false) {
                return array('status' => 'error');
            }
        }

        return array(
            'status' => 'ok',
            'data' => $this->getResource()->fetchRow($id, $fullData)
        );
    }

    /**
     * Creates table entry.
     * @param array $formParams
     * @return array
     */
    public function create($databaseId = null, array $formParams = array(), array $tableDescription = array()) {
        // get databases model
        $databasesModel = new Data_Model_Databases();

        // get roles model
        $rolesModel = new Auth_Model_Roles();

        // create the form object
        $form = new Data_Form_Table(array(
                    'databases' => $databasesModel->getValues(),
                    'databaseId' => $databaseId,
                    'roles' => array_merge(array(0 => 'not published'), $rolesModel->getValues()),
                    'submit' => 'Create table entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $values = $form->getValues();

            //check if entry is already there
            if ($this->getResource()->fetchIdWithName($values['database_id'], $values['name']) !== false) {
                throw new Exception("Table entry already exists.");
            }

            $this->store($values);

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
        $databasesModel = new Data_Model_Databases();

        $form = new Data_Form_Table(array(
                    'databases' => $databasesModel->getValues(),
                    'databaseId' => $databasesModel->getId($entry['database']),
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
                    'submit' => 'Delete table entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteTable($id);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function store(array $values = array(), array $tableDescription = array()) {
        // get autofill flag
        $autofill = null;
        if (array_key_exists('autofill', $values)) {
            $autofill = $values['autofill'];
            unset($values['autofill']);
        }

        // store the values in the database
        $table_id = $this->getResource()->insertRow($values);

        if ($autofill) {
            // get the additional resources
            $descResource = new Data_Model_Resource_Description();
            $columnModel = new Data_Model_Columns();

            // auto create entries for all columns
            $databasesModel = new Data_Model_Databases();
            $db = $databasesModel->getValue($values['database_id']);
            $table = $values['name'];

            try {
                if(empty($tableDescription)) {
                    $tableDescription = $descResource->describeTable($db, $table);
                }

                foreach ($tableDescription['columns'] as $c) {
                    $c['table_id'] = $table_id;
                    $c['table'] = $table;
                    $c['database'] = $db;

                    $columnModel->store($c);
                }
            } catch (Exception $e) {
                $this->getResource()->deleteTable($table_id);
                throw $e;
            }
        }        
    }
}
