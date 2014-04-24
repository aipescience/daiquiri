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

class Query_Model_Examples extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and tablename.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Query_Examples');
    }

    /**
     * Returns all examples. 
     * @return array $response
     */
    public function index() {
        $rows = $this->getResource()->fetchRows();
        foreach ($rows as &$row) {
            $row['publication_role'] = Daiquiri_Auth::getInstance()->getRole($row['publication_role_id']);
        }
        return array('rows' => $rows, 'status' => 'ok');
    }

    /**
     * Creates an example.
     * @param array $formParams
     * @return array $response 
     */
    public function create(array $formParams = array()) {
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Query_Form_Example(array(
            'roles' => $roles,
            'submit' => 'Create Example'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $this->getResource()->insertRow($values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an example.
     * @param int $id
     * @param array $formParams
     * @return array $response 
     */
    public function update($id, array $formParams = array()) {
        // get example from database
        $row = $this->getResource()->fetchRow($id);
        if ($row === false) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Query_Form_Example(array(
            'roles' => $roles,
            'submit' => 'Create Example',
            'row' => $row
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $this->getResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes an example.
     * @param int $id
     * @param array $formParams
     * @return array $response 
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Returns all status messages for export.
     * @return array $response
     */
    public function export() {
        $rows = $this->getResource()->fetchRows();
        foreach ($rows as &$row) {
            $row['publication_role'] = Daiquiri_Auth::getInstance()->getRole($row['publication_role_id']);
            unset($row['id']);
            unset($row['publication_role_id']);
        }
        $data = array(
            'query' => array(
                'examples' => $rows
            )
        );
        return array('data' => $data, 'status' => 'ok');
    }

}
