<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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
        $form = new Data_Form_Databases(array(
            'roles' => $roles,
            'submit' => 'Create database entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // check if entry is already there
                if ($this->getResource()->fetchRowByName($values['name']) !== false) {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form,'Database entry already exists.');
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                // store the values in the database
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
     * Returns a database entry.
     * @param mixed $input int id or array with "db" key
     * @param bool $tables fetch table information
     * @param bool $columns fetch colums information
     * @return array $response
     */
    public function show($input, $tables = false, $columns = false) {
        if (is_int($input)) {
            $row = $this->getResource()->fetchRow($input, $tables, $columns);
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $row = $this->getResource()->fetchRowByName($input['db'], $tables, $columns);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        return array('status' => 'ok','row' => $row);
    }

    /**
     * Updates a database entry.
     * @param mixed $input int id or array with "db" key
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $entry = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $entry = $this->getResource()->fetchRowByName($input['db']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // create the form object
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        $form = new Data_Form_Databases(array(
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

                $this->getResource()->updateRow($entry['id'], $values);
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
            $row = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['db'])) {
                throw new Exception('Either int id or array with "db" key must be provided as $input');
            }
            $row = $this->getResource()->fetchRowByName($input['db']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        return $this->getModelHelper('CRUD')->delete($row['id'], $formParams);
    }

    /**
     * Returns all config databases for export.
     * @return array $response
     */
    public function export() {
        return array(
            'data' => array('databases' => $this->getResource()->fetchRows()),
            'status' => 'ok'
        );
    }

}
