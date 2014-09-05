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

class Core_Model_Messages extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and the database table.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Core_Messages');
    }

    /**
     * Returns all status messages.
     * @return array $response
     */
    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    /**
     * Returns a status message.
     * @param mixed $input id (int) or key (string) of the stautus message
     * @return array $response
     */
    public function show($input) {
        if (is_int($input)) {
            $id = $input;
        } elseif (is_string($input)) {
            $id = $this->getResource()->fetchId(array(
                'where' => array('`key` = ?' => $input)
            ));
            if (empty($id)) {
                throw new Daiquiri_Exception_NotFound();
            }
        } else {
            throw new Exception('$input has wrong type.');
        }

        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Creates a status message.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Core_Form_Messages(array(
            'submit' => 'Create message'
        ));

        // valiadate the form if POST
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
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a status message.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->update($id, $formParams, 'Update message');
    }

    /**
     * Deletes a status message.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete message');
    }

    /**
     * Returns all status messages for export.
     * @return array $response
     */
    public function export() {
        $dbRows = $this->getResource()->fetchRows();

        $data = array();
        foreach ($dbRows as $dbRow) {
            $data[$dbRow['key']] = $dbRow['value'];
        }

        return array(
            'data' => array('messages' => $data),
            'status' => 'ok'
        );
    }

}
