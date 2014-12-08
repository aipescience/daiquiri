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

class Data_Model_Static extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and tablename.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Data_Static');
    }

    /**
     * Returns all static file entries. 
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
     * Creates a static file entry.
     * @param array $formParams
     * @return array $response 
     */
    public function create(array $formParams = array()) {
        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Data_Form_Static(array(
            'roles' => $roles,
            'submit' => 'Create static file entry'
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
     * Updates a static file entry.
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
        $form = new Data_Form_Static(array(
            'roles' => $roles,
            'submit' => 'Update static file entry',
            'entry' => $entry
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
     * Deletes a static file entry.
     * @param int $id
     * @param array $formParams
     * @return array $response 
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Returns all static file entries for export.
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
            'data' => array(
                'static' => $rows
            )
        );
        return array('data' => $data, 'status' => 'ok');
    }

    /**
     * Returns the filename of the static content or raises an exception.
     * @param  string $alias static file alias
     * @param  string $path  url path of the file
     * @return array $response
     */
    public function file($alias, $path) {
        // look for matching static entry
        $row = $this->getResource()->fetchRow(array(
            'where' => array(
                'alias = ?' => $alias
            )
        ));

        // check if the row is there
        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // check permissions
        if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id']) !== true) {
            throw new Daiquiri_Exception_Unauthorized();
        }

        // create absolute file path
        $file = realpath($row['path'] . $path);

        // ensure that the file is not BELOW the give path
        if ($file === false || strpos($file,rtrim($row['path'],'/')) !== 0) {
            throw new Daiquiri_Exception_NotFound();
        }

        // see if the file is there
        if (is_file($file)) {
            return array('status' => 'ok', 'file' => $file);
        } elseif (is_dir($file)) {
            // look for and index file
            $file .= '/index.html';
            if (is_file($file)) {
                return array('status' => 'ok', 'file' => $file);
            } else {
                throw new Daiquiri_Exception_NotFound();
            }
        } else {
            throw new Daiquiri_Exception_NotFound();
        }
    }
}
