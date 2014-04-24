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

}
