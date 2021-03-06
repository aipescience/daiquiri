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

class Meetings_Model_Registration extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource and tablename.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Meetings_Registration');
    }

    /**
     * Returns all registration entries
     * @return array $response
     */
    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    /**
     * Deletes a registration entry.
     * @param int $id id of the registration entry
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete registation');
    }

    /**
     * Registers a participant.
     * @param string $slug slug of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function register($slug, array $formParams = array()) {
        // get models
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['registration_publication_role_id'])) {
            return array(
                'status' => 'forbidden',
                'message' => $meeting['registration_message']
            );
        }

        // get user if one is logged in
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        if ($userId > 0) {
            // get the user model for getting user details
            $userModel = new Auth_Model_User();
            $user = $userModel->getResource()->fetchRow($userId);
        } else {
            $user = array();
        }

        // create the form object
        $form = new Meetings_Form_Registration(array(
            'submit'=> 'Register for this meeting',
            'meeting' => $meeting,
            'user' => $user
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();
                $values['meeting_id'] = $meeting['id'];

                $values['details'] = array();
                foreach ($meeting['participant_detail_keys'] as $keyId => $detailKey) {
                    if (is_array($values[$detailKey['key']])) {
                        $values['details'][$keyId] = Zend_Json::encode($values[$detailKey['key']]);
                    } else if ($values[$detailKey['key']] === null) {
                        $values['details'][$keyId] = Zend_Json::encode(array());
                    } else {
                        $values['details'][$keyId] = $values[$detailKey['key']];
                    }
                    unset($values[$detailKey['key']]);
                }

                $values['contributions'] = array();
                foreach ($meeting['contribution_types'] as $contributionTypeId => $contributionType) {
                    if ($values[$contributionType . '_bool'] === '1') {
                        $values['contributions'][$contributionTypeId] = array(
                            'title' => $values[$contributionType . '_title'],
                            'abstract' => $values[$contributionType . '_abstract'],
                        );
                    } else {
                        $values['contributions'][$contributionTypeId] = false;
                    }
                    unset($values[$contributionType . '_bool']);
                    unset($values[$contributionType . '_title']);
                    unset($values[$contributionType . '_abstract']);
                }

                // get the right status
                $participantStatusModel = new Meetings_Model_ParticipantStatus();
                if (empty(Daiquiri_Config::getInstance()->meetings->autoAccept)) {
                    $values['status_id'] = $participantStatusModel->getResource()->fetchId(array(
                        'where' => array('`status` = "registered"')
                    ));
                } else {
                    $values['status_id'] = $participantStatusModel->getResource()->fetchId(array(
                        'where' => array('`status` = "accepted"')
                    ));
                }

                if (Daiquiri_Config::getInstance()->meetings->validation) {
                    $code = $this->createRandomString(32);

                    // store the values in the database
                    $id = $this->getResource()->insertRow(array(
                        'email' => $values['email'],
                        'code' => $code,
                        'values' => Zend_Json::encode($values),
                        'meeting_id' => $meeting['id']
                    ));

                    // prepare and send mail
                    $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/meetings/registration/validate/id/' . $id . '/code/' . $code;

                    $this->getModelHelper('mail')->send('meetings.validate',
                        array(
                            'to' => $values['email'],
                            'meeting' => $meeting['title'],
                            'firstname' => $values['firstname'],
                            'lastname' => $values['lastname'],
                            'link' => $link
                        )
                    );

                    return array('status' => 'validate');
                } else {
                    $participantModel = new Meetings_Model_Participants();
                    $id = $participantModel->getResource()->insertRow($values);
                    $participant = $participantModel->getResource()->fetchRow($id);

                    $mailValues = array(
                        'to' => $participant['email'],
                        'meeting' => $meeting['title'],
                        'firstname' => $participant['firstname'],
                        'lastname' => $participant['lastname'],
                        'affiliation' => $participant['affiliation'],
                        'email' => $participant['email'],
                        'arrival' => $participant['arrival'],
                        'departure' => $participant['departure']
                    );

                    foreach($meeting['participant_detail_keys'] as $d) {
                        if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('radio','select'))) {
                            $options = Zend_Json::decode($d['options']);
                            $mailValues[$d['key']] = $options[$participant['details'][$d['key']]];
                        } else if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('checkbox','multiselect'))) {
                            $options = Zend_Json::decode($d['options']);

                            $values = array();
                            foreach (Zend_Json::decode($participant['details'][$d['key']]) as $value_id) {
                                $values[] = $options[$value_id];
                            }

                            $mailValues[$d['key']] = implode(', ',$values);
                        } else {
                            $mailValues[$d['key']] = $participant['details'][$d['key']];
                        }
                    }
                    foreach ($meeting['contribution_types'] as $contribution_type) {
                        if (!empty($participant['contributions'][$contribution_type])) {
                            $mailValues[$contribution_type . '_title'] = $participant['contributions'][$contribution_type]['title'];
                            $mailValues[$contribution_type . '_abstract'] = $participant['contributions'][$contribution_type]['abstract'];
                        } else {
                            $mailValues[$contribution_type . '_title'] = '---';
                        }
                    }

                    $this->getModelHelper('mail')->send('meetings.register', $mailValues);

                    return array('status' => 'ok');
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form', 'message' => $meeting['registration_message']);
    }

    /**
     * Validates a participant.
     * @param int $id id of the registration entry
     * @param string $code validation code
     * @param array $formParams
     * @return array $response
     */
    public function validate($id, $code) {
        $registration = $this->getResource()->fetchRow($id);

        if ($registration['code'] === $code) {
            $values = Zend_Json::decode($registration['values']);

            // get the participant resource and store values in the database
            $participantModel = new Meetings_Model_Participants();
            $id = $participantModel->getResource()->insertRow($values);
            $participant = $participantModel->getResource()->fetchRow($id);

            // delete from registration table
            $this->getResource()->deleteRow($id);

            $meetingsModel = new Meetings_Model_Meetings();
            $meeting = $meetingsModel->getResource()->fetchRow($values['meeting_id']);

            $mailValues = array(
                'to' => $participant['email'],
                'meeting' => $meeting['title'],
                'firstname' => $participant['firstname'],
                'lastname' => $participant['lastname'],
                'affiliation' => $participant['affiliation'],
                'email' => $participant['email'],
                'arrival' => $participant['arrival'],
                'departure' => $participant['departure']
            );

            foreach($meeting['participant_detail_keys'] as $d) {
                if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('radio','select'))) {
                    $options = Zend_Json::decode($d['options']);
                    $mailValues[$d['key']] = $options[$participant['details'][$d['key']]];
                } else if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('checkbox','multiselect'))) {
                    $options = Zend_Json::decode($d['options']);

                    $values = array();
                    foreach (Zend_Json::decode($participant['details'][$d['key']]) as $value_id) {
                        $values[] = $options[$value_id];
                    }

                    $mailValues[$d['key']] = $values;
                } else {
                    $mailValues[$d['key']] = $participant['details'][$d['key']];
                }
            }
            foreach ($meeting['contribution_types'] as $contribution_type) {
                if (!empty($participant['contributions'][$contribution_type])) {
                    $mailValues[$contribution_type . '_title'] = $participant['contributions'][$contribution_type]['title'];
                    $mailValues[$contribution_type . '_abstract'] = $participant['contributions'][$contribution_type]['abstract'];
                } else {
                    $mailValues[$contribution_type . '_title'] = '---';
                }
            }

            $this->getModelHelper('mail')->send('meetings.register', $mailValues);

            return array('status' => 'ok');
        } else {
            return array(
                'status' => 'error',
                'error' => 'user or code is not valid'
            );
        }
    }
}
