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

class Auth_Model_Details extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Details');
    }

    /**
     * Returns a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @return array $response
     */
    public function show($userId, $key) {
        if (empty($userId) || empty($key)) {
            throw new Daiquiri_Exception_BadRequest('id and/or key arguments are missing. ');
        }

        $detail = $this->getResource()->fetchValue($userId, $key);
        if ($detail === false) {
            throw new Daiquiri_Exception_NotFound();
        } else {
            return array('status' => 'ok', 'data' => $detail);
        }
    }

    /**
     * Creates a user detail.
     * @param int $userId id of the user
     * @param array $formParams
     * @return array $response
     */
    public function create($userId, array $formParams = array()) {
        if (empty($userId)) {
            throw new Daiquiri_Exception_BadRequest('id argument is missing. ');
        }

        // create the form object
        $form = new Auth_Form_Details(array(
            'submit' => 'Create detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                if ($this->getResource()->fetchValue($userId, $values['key']) === false) {
                    // store the details
                    $this->getResource()->insertValue($userId, $values['key'], $values['value']);
                    return array('status' => 'ok');
                } else {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form, array('key' => 'The key is already stored in the database.'));
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param array $formParams
     * @return array $response
     */
    public function update($userId, $key, array $formParams = array()) {
        if (empty($userId) || empty($key)) {
            throw new Daiquiri_Exception_BadRequest('id and/or key arguments are missing. ');
        }

        // get the detail from the database
        $value = $this->getResource()->fetchValue($userId, $key);
        if ($value === false) {
            throw new Daiquiri_Exception_NotFound();
        }

        // create the form object
        $form = new Auth_Form_Details(array(
            'key' => $key,
            'value' => $value,
            'submit' => 'Update detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $this->getResource()->updateValue($userId, $key, $values['value']);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a user detail.
     * @param int $userId id of the user
     * @param string $key key of the detail
     * @param array $formParams
     * @return array $response
     */
    public function delete($userId, $key, array $formParams = array()) {
        if (empty($userId) || empty($key)) {
            throw new Daiquiri_Exception_BadRequest('id and/or key arguments are missing. ');
        }

        // check if the key is there
        if ($this->getResource()->fetchValue($userId, $key) === false) {
            return array('status' => 'error', 'error' => 'Key not found');
        } else if (in_array($key, Daiquiri_Config::getInstance()->auth->details->toArray())) {
            return array('status' => 'error', 'error' => 'Key is protected');
        }

        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Delete detail'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $this->getResource()->deleteValue($userId, $key);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
