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

class Auth_Model_Account extends Daiquiri_Model_Abstract {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_User');
    }

    /**
     * Returns the credentials of the user which is currently logged in.
     * @return array $response
     */
    public function show() {
        // get id
        $id = Daiquiri_Auth::getInstance()->getCurrentId();

        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Updates the credentials of the currently logged in user.
     * @param array $formParams
     * @return array $response
     */
    public function update(array $formParams = array()) {
        // get id
        $id = Daiquiri_Auth::getInstance()->getCurrentId();

        // get user
        $user = $this->getResource()->fetchRow($id);

        // get user detail keys model
        $detailKeyModel = new Auth_Model_DetailKeys();
        $detailKeys = $detailKeyModel->getResource()->fetchRows();

        // create the form object
        $form = new Auth_Form_Account(array(
            'user' => $this->getResource()->fetchRow($id),
            'detailKeys' => $detailKeys,
            'changeUsername' => Daiquiri_Config::getInstance()->auth->changeUsername,
            'changeEmail' => Daiquiri_Config::getInstance()->auth->changeEmail,
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // process the details
                $changed = false;
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

                    if ($values['details'][$detailKey['key']] != $user['details'][$detailKey['key']]) {
                        $changed = true;
                    }
                }
                if (Daiquiri_Config::getInstance()->auth->changeUsername && $values['username'] != $user['username']) {
                    $changed = true;
                }
                if (Daiquiri_Config::getInstance()->auth->changeEmail && $values['email'] != $user['email']) {
                    $changed = true;
                }

                if ($changed) {
                    // update the user
                    $this->getResource()->updateRow($id, $values);

                    // log the event
                    Daiquiri_Log::getInstance()->notice('account updated by user');

                    // send a notification
                    if (Daiquiri_Config::getInstance()->core->notification->updateUser) {
                        $newUser = $this->getResource()->fetchRow($id);
                        $this->getModelHelper('notification')->updateUser($user, $newUser);
                    }
                }
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }
}
