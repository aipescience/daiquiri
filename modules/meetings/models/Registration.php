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
     * Returns one specific registration entry.
     * @param int $id id of the registration entry
     * @return array $response
     */
    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
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
     * @param int $meetingId id of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function register($meetingId, array $formParams = array()) {
        // get models
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($meetingId);

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['registration_publication_role_id'])) {
            return array(
                'status' => 'error',
                'message' => $meeting['registration_message']
            );
        }

        // create the form object
        $form = new Meetings_Form_Registration(array(
            'submit'=> 'Register for this meeting',
            'meeting' => $meeting
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();
                $values['meeting_id'] = $meetingId;
                $values['details'] = array();
                foreach ($meeting['participant_detail_keys'] as $key_id => $key) {
                    $values['details'][$key_id] = $values[$key];
                    unset($values[$key]);
                }
                $values['contributions'] = array();
                foreach ($meeting['contribution_types'] as $contributionTypeId => $contributionType) {
                    if ($values[$contributionType . '_bool'] === '1') {
                        $values['contributions'][$contributionTypeId] = array(
                            'title' => $values[$contributionType . '_title'],
                            'abstract' => $values[$contributionType . '_abstract'],
                        );    
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
                        'meeting_id' => $meetingId
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
                    $participantModel->getResource()->insertRow($values);

                    $mailValues = array(
                        'to' => $values['email'],
                        'meeting' => $meeting['title'],
                        'firstname' => $values['firstname'],
                        'lastname' => $values['lastname'],
                        'affiliation' => $values['affiliation'],
                        'email' => $values['email'],
                        'arrival' => $values['arrival'],
                        'departure' => $values['departure']
                    );
                    foreach ($meeting['participant_detail_keys'] as $key => $value) {
                        $mailValues[$value] = $values['details'][$key];
                    }
                    foreach ($meeting['contribution_types'] as $key => $contribution_type) {
                        if (!empty($values['contributions'][$key])) {
                            $mailValues[$contribution_type . '_title'] = $values['contributions'][$key]['title'];
                            $mailValues[$contribution_type . '_abstract'] = $values['contributions'][$key]['abstract'];
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
            $participantResource = new Meetings_Model_Resource_Participants();
            $participantResource->insertRow($values);

            // delete from registration table
            $this->getResource()->deleteRow($id);

            $meetingsModel = new Meetings_Model_Meetings();
            $meeting = $meetingsModel->getResource()->fetchRow($values['meeting_id']);

            $mailValues = array(
                'to' => $values['email'],
                'meeting' => $meeting['title'],
                'firstname' => $values['firstname'],
                'lastname' => $values['lastname'],
                'affiliation' => $values['affiliation'],
                'email' => $values['email'],
                'arrival' => $values['arrival'],
                'departure' => $values['departure']
            );
            foreach ($meeting['participant_detail_keys'] as $key => $value) {
                $mailValues[$value] = $values['details'][$key];
            }
            foreach ($meeting['contribution_types'] as $key => $contribution_type) {
                if (!empty($values['contributions'][$key])) {
                    $mailValues[$contribution_type . '_title'] = $values['contributions'][$key]['title'];
                    $mailValues[$contribution_type . '_abstract'] = $values['contributions'][$key]['abstract'];
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
