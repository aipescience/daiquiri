<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        $form = new Data_Form_Columns(array(
            'tables' => $tablesResource->fetchValues('name'),
            'tableId' => $tableId,
            'roles' => $roles,
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
                if ($this->getResource()->fetchRowByName($db, $table, $values['name']) !== false) {
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
            $row = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $row = $this->getResource()->fetchRowByName($input['db'],$input['table'],$input['column']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        $row['publication_role'] = Daiquiri_Auth::getInstance()->getRole($row['publication_role_id']);

        return array('status' => 'ok','row' => $row);
    }

    /**
     * Updates a column entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function update($input, array $formParams = array()) {
        if (is_int($input)) {
            $entry = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $entry = $this->getResource()->fetchRowByName($input['db'],$input['table'],$input['column']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get tables and ucds
        $tablesResource = new Data_Model_Resource_Tables();
        $ucdsResource = new Daiquiri_Model_Resource_Table();
        $ucdsResource->setTablename('Data_UCD');
        
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        
        $form = new Data_Form_Columns(array(
            'tables' => $tablesResource->fetchValues('name'),
            'tableId' => $entry['table_id'],
            'ucds' => $ucdsResource->fetchRows(),
            'roles' => $roles,
            'submit' => 'Update column entry',
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

                try {
                    $this->getResource()->updateRow($entry['id'], $values);
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
     * Deletes a column entry.
     * @param mixed $input int id or array with "db","table" and "column" keys
     * @param array $formParams
     * @return array $response
     */
    public function delete($input, array $formParams = array()) {
        if (is_int($input)) {
            $row = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['db']) || empty($input['table']) || empty($input['column'])) {
                throw new Exception('Either int id or array with "db","table" and "column" keys must be provided as $input');
            }
            $row = $this->getResource()->fetchRowByName($input['db'],$input['table'],$input['column']);
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        return $this->getModelHelper('CRUD')->delete($row['id'], $formParams);
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
