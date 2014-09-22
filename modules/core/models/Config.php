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

class Core_Model_Config extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and the database table.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Core_Config');
        $this->_cols = array('key','value');
    }

    /**
     * Returns all config entries.
     * @return array $response
     */
    public function index() {
        return array(
            'status' => 'ok',
            'rows' => $this->getResource()->fetchRows(array(
                'order' => 'key ASC'
            ))
        );
    }

    /**
     * Creates a config entry.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Core_Form_Config(array(
            'submit' => 'Create config entry'
        ));

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
        return $this->getModelHelper('CRUD')->update($id, $formParams, 'Update config entry');
    }

    /**
     * Deletes a config entry message.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete config entry');
    }

    /**
     * Returns all config entries for export.
     * @return array $response
     */
    public function export() {
        return array(
            'data' => array('config' => Daiquiri_Config::getInstance()->getConfig()->toArray()),
            'status' => 'ok'
        );
    }

}
