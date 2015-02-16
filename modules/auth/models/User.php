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

class Auth_Model_User extends Daiquiri_Model_Table {

    /**
     * Possible options for each user.
     * @var array $_options
     */
    private $_options = array(

        'Confirm' => array(
            'url' => '/auth/registration/confirm',
            'permission' => 'confirm',
            'resource' => 'Auth_Model_Registration',
            'status' => array('registered')
        ),
        'Reject' => array(
            'url' => '/auth/registration/reject',
            'permission' => 'reject',
            'resource' => 'Auth_Model_Registration',
            'status' => array('registered')
        ),
        'Activate' => array(
            'url' => '/auth/registration/activate',
            'permission' => 'activate',
            'resource' => 'Auth_Model_Registration',
            'status' => array('registered', 'confirmed')
        ),
        'Disable' => array(
            'url' => '/auth/registration/disable',
            'permission' => 'disable',
            'resource' => 'Auth_Model_Registration',
            'status' => array('active'),
            'role' => array('user')
        ),
        'Reenable' => array(
            'url' => '/auth/registration/reenable',
            'permission' => 'reenable',
            'resource' => 'Auth_Model_Registration',
            'status' => array('disabled')
        ),

        'Password' => array(
            'url' => '/auth/password/set',
            'permission' => 'set',
            'resource' => 'Auth_Model_Password'
        )
    );

    /**
     * Constructor. Sets resource object and cols.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
        $this->_cols = array('id', 'username', 'email', 'role', 'status');
    }

    /**
     * Returns the columns of the user table specified by some parameters.
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        $cols = array();
        foreach ($this->_cols as $colname) {
            $col = array(
                'name' => $colname,
                'sortable' => true
            );
            if ($colname === 'id') {
                $col['width'] = 40;
            } else if ($colname === 'username') {
                $col['width'] = 180;
            } else if ($colname === 'email') {
                $col['width'] = 180;
            } else if ($colname === 'role') {
                $col['width'] = 80;
            } else if ($colname === 'status') {
                $col['width'] = 80;
            } else {
                $col['width'] = 100;
            }
            $cols[] = $col;
        }
        $cols[] = array(
            'name' => 'options',
            'width' => '200px',
            'sortable' => 'false'
        );

        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Returns the rows of the user table specified by some parameters.
     * @param array $params get params of the request
     * @return array $response
     */
    public function rows(array $params = array()) {
        // parse params
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);

        // get the data from the database
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add an options to destroy the session
        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->_cols as $col) {
                $row[] = $dbRow[$col];
            }

            $status = $dbRow['status'];

            // add options
            $options = array();
            foreach (array(
                'Show' => array(
                    'url' => '/auth/user/show',
                    'permission' => 'show',
                    'resource' => 'Auth_Model_User'
                ),
                'Update' => array(
                    'url' => '/auth/user/update',
                    'permission' => 'update',
                    'resource' => 'Auth_Model_User'
                ),
                'Delete' => array(
                    'url' => '/auth/user/delete',
                    'permission' => 'delete',
                    'resource' => 'Auth_Model_User'
                )) as $key => $value) {
                $option = $this->internalLink(array(
                        'text' => $key,
                        'href' => $value['url'] . '/id/' . $dbRow['id'],
                        'resource' => $value['resource'],
                        'permission' => $value['permission'],
                        'class' => 'daiquiri-admin-option'));
                if (!empty($option)) {
                   $options[] = $option;
                }
            }

            // add options for registration workflow
            if (!in_array($dbRow['role'],array('manager','admin'))) {
                foreach (array(
                    'Confirm' => array(
                        'url' => '/auth/registration/confirm',
                        'permission' => 'confirm',
                        'resource' => 'Auth_Model_Registration',
                        'prerequisites' => array('registered')
                    ),
                    'Reject' => array(
                        'url' => '/auth/registration/reject',
                        'permission' => 'reject',
                        'resource' => 'Auth_Model_Registration',
                        'prerequisites' => array('registered')
                    ),
                    'Activate' => array(
                        'url' => '/auth/registration/activate',
                        'permission' => 'activate',
                        'resource' => 'Auth_Model_Registration',
                        'prerequisites' => array('registered', 'confirmed')
                    ),
                    'Disable' => array(
                        'url' => '/auth/registration/disable',
                        'permission' => 'disable',
                        'resource' => 'Auth_Model_Registration',
                        'prerequisites' => array('active')
                    ),
                    'Reenable' => array(
                        'url' => '/auth/registration/reenable',
                        'permission' => 'reenable',
                        'resource' => 'Auth_Model_Registration',
                        'prerequisites' => array('disabled')
                    )) as $key => $value) {
                    if (in_array($status, $value['prerequisites'])) {
                        $option = $this->internalLink(array(
                                'text' => $key,
                                'href' => $value['url'] . '/id/' . $dbRow['id'],
                                'resource' => $value['resource'],
                                'permission' => $value['permission'],
                                'class' => 'daiquiri-admin-option'));
                        if (!empty($option)) {
                           $options[] = $option;
                        }
                    }
                }
            }

            // add option for set password
            $option = $this->internalLink(array(
                    'text' => 'Password',
                    'href' => '/auth/password/set/id/' . $dbRow['id'],
                    'resource' => 'Auth_Model_Password',
                    'permission' => 'set',
                    'class' => 'daiquiri-admin-option'));
            if (!empty($option)) {
               $options[] = $option;
            }

            // add options to row
            $row[] = implode('&nbsp;',$options);

            $rows[] = $row;
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    /**
     * Returns the credentials of a given user from the database.
     * @param int $id id of the user
     * @return array $response
     */
    public function show($id) {
        // get user detail keys model
        $detailKeyModel = new Auth_Model_DetailKeys();
        $detailKeys = $detailKeyModel->getResource()->fetchRows();

        $row = $this->getResource()->fetchRow($id);

        foreach($detailKeys as $d) {
            if (in_array(Auth_Model_DetailKeys::$types[$d['type_id']], array('radio','select'))) {
                $options = Zend_Json::decode($d['options']);
                $row['details'][$d['key']] = $options[$row['details'][$d['key']]];
            } else if (in_array(Auth_Model_DetailKeys::$types[$d['type_id']], array('checkbox','multiselect'))) {
                $options = Zend_Json::decode($d['options']);

                $values = array();
                foreach (Zend_Json::decode($row['details'][$d['key']]) as $value_id) {
                    $values[] = $options[$value_id];
                }

                $row['details'][$d['key']] = $values;
            }
        }
        return array('status' => 'ok', 'row' => $row);
    }

    /**
     * Creates a new user.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // get the status model, the roles model and the roles
        $status = Daiquiri_Auth::getInstance()->getStatus();
        $roles = Daiquiri_Auth::getInstance()->getRoles();
        unset($roles[1]); // unset the guest user

        // get user detail keys model
        $detailKeyModel = new Auth_Model_DetailKeys();
        $detailKeys = $detailKeyModel->getResource()->fetchRows();

        // create the form object
        $form = new Auth_Form_CreateUser(array(
            'detailKeys' => $detailKeys,
            'status' => $status,
            'roles' => $roles
        ));

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // unset some elements
                unset($values['confirm_password']);

                // process the details
                $values['details'] = array();
                foreach ($detailKeys as $detailKey) {
                    if (is_array($values[$detailKey['key']])) {
                        $values['details'][$detailKey['key']] = Zend_Json::encode($values[$detailKey['key']]);
                    } else if ($values[$detailKey['key']] === null) {
                        $values['details'][$detailKey['key']] = Zend_Json::encode(array());
                    }else {
                        $values['details'][$detailKey['key']] = $values[$detailKey['key']];
                    }
                    unset($values[$detailKey['key']]);
                }

                // create the user
                $id = $this->getResource()->insertRow($values);

                // log the event and return
                Daiquiri_Log::getInstance()->notice("user '{$values['username']}' created");
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }
        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an existing user.
     * @param int $id id of the user
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        // get the status model, the roles model and the roles
        $status = Daiquiri_Auth::getInstance()->getStatus();
        $roles = Daiquiri_Auth::getInstance()->getRoles();
        unset($roles[1]); // unset the guest user

        // get user
        $user = $this->getResource()->fetchRow($id);
        if (empty($user)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get user detail keys model
        $detailKeyModel = new Auth_Model_DetailKeys();
        $detailKeys = $detailKeyModel->getResource()->fetchRows();

        // create the form object
        $form = new Auth_Form_UpdateUser(array(
            'detailKeys' => $detailKeys,
            'status' => $status,
            'roles' => $roles,
            'changeUsername' => Daiquiri_Config::getInstance()->auth->changeUsername,
            'changeEmail' => Daiquiri_Config::getInstance()->auth->changeEmail,
            'user' => $user
        ));

        // check if request is POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // process the details
                $values['details'] = array();
                foreach ($detailKeys as $detailKey) {
                    if (is_array($values[$detailKey['key']])) {
                        $values['details'][$detailKey['key']] = Zend_Json::encode($values[$detailKey['key']]);
                    } else if ($values[$detailKey['key']] === null) {
                        $values['details'][$detailKey['key']] = Zend_Json::encode(array());
                    } else {
                        $values['details'][$detailKey['key']] = $values[$detailKey['key']];
                    }
                    unset($values[$detailKey['key']]);
                }

                // update the user and redirect
                $this->getResource()->updateRow($id, $values);

                // log the event and return
                Daiquiri_Log::getInstance()->notice("user '{$user['username']}' updated");
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes an existing user.
     * @param int $id id of the user
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Delete user'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // delete the user and redirect
                $this->getResource()->deleteRow($id);

                // invalidate the session of the user
                $resource = new Auth_Model_Resource_Sessions();
                foreach ($resource->fetchAuthSessionsByUserId($id) as $session) {
                    $resource->deleteRow($session);
                };

                // log the event and return
                Daiquiri_Log::getInstance()->notice("user deleted by admin (user_id: {$id})");
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns the user information in a convenient text-only format
     * @param string $status display only participants with a certain status
     * @return array $response
     */
    public function export($status = false) {
        return array(
            'status' => 'ok',
            'rows' => $this->getResource()->fetchEmails($status)
        );
    }
}
