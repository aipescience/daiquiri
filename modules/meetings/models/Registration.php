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

/*
 * Placeholder for future conference model development. Only initial implementation
 * provided...
 *
 */

class Meetings_Model_Registration extends Daiquiri_Model_Abstract {

    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Simple');
        $this->getResource()->setTablename('Meetings_Registration');
    }

    public function register($meetingId, array $formParams = array()) {
        // get models
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($meetingId);

        // create the form object
        $form = new Meetings_Form_Participant(array(
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

                $code = $this->createRandomString(32);

                // store the values in the database
                $id = $this->getResource()->insertRow(array(
                    'email' => $values['email'],
                    'code' => $code,
                    'values' => Zend_Json::encode($values)
                ));

                $link = Daiquiri_Config::getInstance()->getSiteUrl() . '/meetings/registration/validate/id/' . $id . '/code/' . $code;

                Zend_Debug::dump($link); // die(0);

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

            return array('status' => 'ok');
        } else {
            return array(
                'status' => 'error',
                'error' => 'user or code is not valid'
            );
        }
    }


}