<?php

/*  
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class Meetings_Model_Registration extends Daiquiri_Model_Table {

    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Simple');
        $this->getResource()->setTablename('Meetings_Registration');
    }

    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete registation');
    }

    public function register($meetingId, array $formParams = array()) {
        // get models
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($meetingId);

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['registration_publication_role_id'])) {
            return array(
                'status' => 'error'
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

                $participantStatusModel = new Meetings_Model_ParticipantStatus();
                // get the registered status
                $values['status_id'] = $participantStatusModel->getResource()->fetchId(array(
                    'where' => array('`status` = "registered"')
                ));
                    
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
                } else {
                    $participantModel = new Meetings_Model_Participants();
                    $participantModel->getResource()->insertRow($values);

                    $this->getModelHelper('mail')->send('meetings.register', 
                        array(
                            'to' => $values['email'],
                            'meeting' => $meeting['title'],
                            'firstname' => $values['firstname'],
                            'lastname' => $values['lastname']
                        )
                    );
                }

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'form' => $form
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function validate($id, $code) {
        $registration = $this->getResource()->fetchRow($id);

        if ($registration['code'] === $code) {
            $values = Zend_Json::decode($registration['values']);

            // get the participant resource and store values in the database
            $participantResource = new Meetings_Model_Resource_Participants();
            $participantResource->insertRow($values);

            // delete from registration table
            $this->getResource()->deleteRow($id);

            return array('status' => 'ok');
        } else {
            return array(
                'status' => 'error',
                'error' => 'user or code is not valid'
            );
        }
    }
}
