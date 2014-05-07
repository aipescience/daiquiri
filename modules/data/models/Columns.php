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

class Data_Model_Columns extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Columns');
    }

    /**
     * Creates column entry.
     * @param int $tableId id of the parent table (only form default value)
     * @param array $formParams
     * @return array $response
     */
    public function create($tableId = null, array $formParams = array()) {
        // get tables and ucds
        $tablesResource = new Data_Model_Resource_Tables();
        $ucdsResource = new Daiquiri_Model_Resource_Table();
        $ucdsResource->setTablename('Data_UCD');

        $form = new Data_Form_Columns(array(
            'tables' => $tablesResource->fetchValues('name'),
            'tableId' => $tableId,
            'ucds' => $ucdsResource->fetchRows(),
            'submit' => 'Create column entry'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $values = $form->getValues();

                $tmp = explode('.',$tables[$values['table_id']]);
                $db = $tmp[0];
                $table = $tmp[1];
                if ($this->getResource()->fetchIdByName($db, $table, $values['name']) !== false) {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form,'Column entry already exists.');
                }

                if(array_key_exists("ucd_list", $values)) {
                    unset($values['ucd_list']);
                }

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                $this->getResource()->insertRow($values);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns a column entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @return array $response
     */
    public function show($input) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table'],$input['column']);
            if (empty($id)) {
                throw new Daiquiri_Exception_NotFound();
            }
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Updates a column entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table'],$input['column']);
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

        // get tables and ucds
        $tablesResource = new Data_Model_Resource_Tables();
        $ucdsResource = new Daiquiri_Model_Resource_Table();
        $ucdsResource->setTablename('Data_UCD');
        
        $form = new Data_Form_Columns(array(
            'tables' => $tablesResource->fetchValues('name'),
            'tableId' => $entry['table_id'],
            'ucds' => $ucdsResource->fetchRows(),
            'submit' => 'Create column entry',
            'entry' => $entry
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                unset($values['ucd_list']);

                // check if the order needs to be set to NULL
                if ($values['order'] === '') {
                    $values['order'] = NULL;
                }

                $values['database'] = $entry['database'];
                $values['table'] = $entry['table'];
                $this->getResource()->updateRow($id, $values);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a column entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function delete($input, array $formParams = array()) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $id = $this->getResource()->fetchIdByName($input['db'],$input['table'],$input['column']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Returns all columns for export.
     * @return array $response
     */
    public function export() {
        return array(
            'data' => array('columns' => $this->getResource()->fetchRows()),
            'status' => 'ok'
        );
    }

}
