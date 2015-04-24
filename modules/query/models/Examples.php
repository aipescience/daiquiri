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
        $rows = $this->getResource()->fetchRows(array(
            'order' => 'order ASC'
        ));
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
            'submit' => 'Create example'
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
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Query_Form_Example(array(
            'roles' => $roles,
            'submit' => 'Update example',
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
