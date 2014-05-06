<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

                // check if entry is already there
                $database = $databases[$values['database_id']];
                if ($this->getResource()->fetchIdByName($database, $values['name']) !== false) {
                    throw new Exception("Table entry already exists.");
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                try {
                    $this->getResource()->insertRow($values);
                } catch (Exception $e) {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form, $e->getMessage());
                }

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
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table']);
            if (empty($id)) {
                throw new Daiquiri_Exception_NotFound();
            }
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
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table']);
            if (empty($id)) {
                throw new Daiquiri_Exception_NotFound();
            }
        } else {
            throw new Exception('$input has wrong type.');
        }

        // get the entry
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
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
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Returns all tables for export.
     * @return array $response
     */
    public function export() {
        return array(
            'data' => array('tables' => $this->getResource()->fetchRows()),
            'status' => 'ok'
        );
    }
}
