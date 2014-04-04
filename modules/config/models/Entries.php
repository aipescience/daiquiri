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

class Config_Model_Entries extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and the database table.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Config_Entries');
        $this->_cols = array('key','value');
    }

    /**
     * Returns all config entries.
     * @return array
     */
    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    /**
     * Creates a config entry.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Config_Form_Entries();

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                $rows = $this->getResource()->fetchRows(array(
                    'where' => array('`key` = ?' => $values['key'])
                ));

                if (empty($rows)) {
                    // store the details
                    $this->getResource()->insertRow($values);
                    return array('status' => 'ok');
                } else {
                    $form->setDescription('Key already stored');
                    return array(
                        'form' => $form,
                        'status' => 'error',
                        'error' => 'Key already stored'
                    );
                }
            } else {
                return array(
                    'form' => $form,
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a config entry message.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->update($id, $formParams, 'Update entry');
    }

    /**
     * Deletes a config entry message.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete entry');
    }

    /**
     * Returns all config entries for export.
     * @return array $response
     */
    public function export() {
        return array(
            'data' => Daiquiri_Config::getInstance()->getConfig(),
            'status' => 'ok'
        );
    }

}
