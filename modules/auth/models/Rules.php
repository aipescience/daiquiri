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

class Auth_Model_Rules extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Auth_Rules');
    }

    /**
     * Creates a rule entry for the ACLs.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Rules();

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // get additional models
                $roleModel = new Auth_Model_Roles();
                $appsModel = new Auth_Model_Apps();
                $resourceModel = new Auth_Model_Resources();

                // get proper ids
                $roleId = $roleModel->getResource()->fetchId(array('where' => array('role=?' => $values['role'])));
                if ($roleId === false) {
                    // check if it is an app
                    $roleId = $appsModel->getResource()->fetchId(array('where' => array('appname=?' => $values['role'])));
                    if ($roleId === false) {
                        return array(
                            'form' => $form,
                            'status' => 'error',
                            'error' => 'role "' . $values['role'] . '" not found in database'
                        );
                    } else {
                        $roleId = - $roleId;
                    }
                }
                if ($values['resource'] === '') {
                    $resourceId = null;
                } else {
                    $resourceId = $resourceModel->getResource()->fetchId(array('where' => array('resource=?' => $values['resource'])));
                    if ($resourceId === false) {
                        return array(
                            'form' => $form,
                            'status' => 'error',
                            'error' => 'resource "' . $values['resource'] . '" not found in database'
                        );
                    }
                }
                if ($values['permissions'] === '') {
                    $values['permissions'] = null;
                }

                // insert into database
                $this->getResource()->insertRow(array(
                    'role_id' => $roleId,
                    'resource_id' => $resourceId,
                    'permissions' => $values['permissions']
                ));

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
