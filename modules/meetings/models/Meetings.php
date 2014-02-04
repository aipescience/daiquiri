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

class Meetings_Model_Meetings extends Daiquiri_Model_Table {

    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Meetings');
    }

    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    public function create(array $formParams = array()) {
        // get models
        $contributionTypeModel = new Meetings_Model_ContributionTypes();
        $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        
        // create the form object
        $form = new Meetings_Form_Meeting(array(
            'submit'=> 'Create meeting',
            'contributionTypes' => $contributionTypeModel->getResource()->fetchValues('contribution_type'),
            'participantDetailKeys' => $participantDetailKeysModel->getResource()->fetchValues('key'),
            'roles' => $roles
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // store the values in the database
                $this->getResource()->insertRow($values);

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

    public function update($id , array $formParams = array()) {
        // get meeting from teh database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get models
        $contributionTypeModel = new Meetings_Model_ContributionTypes();
        $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Meetings_Form_Meeting(array(
            'submit'=> 'Update meeting',
            'contributionTypes' => $contributionTypeModel->getResource()->fetchValues('contribution_type'),
            'participantDetailKeys' => $participantDetailKeysModel->getResource()->fetchValues('key'),
            'roles' => $roles,
            'entry' => $entry
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // store the values in the database
                $this->getResource()->updateRow($id, $values);

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

    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    public function mails($id, array $formParams = array()) {
        // get meeting from the database
        $meeting = $this->getResource()->fetchRow($id);
        if (empty($meeting)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get all accepted and all rejected participants
        $participantModel = new Meetings_Model_Participants();
        $accepted = $participantModel->getResource()->fetchRows(array(
            'where' => array('status = "accepted"')
        ));
        $rejected = $participantModel->getResource()->fetchRows(array(
            'where' => array('status = "rejected"')
        ));

        // get mail templates
        $templateModel = new Config_Model_Templates();
        $acceptTemplate = $templateModel->getResource()->fetchRow('meetings.accept');
        $rejectTemplate = $templateModel->getResource()->fetchRow('meetings.reject');

        // create the form object
        $form = new Meetings_Form_Mails(array(
            'accepted' => $accepted,
            'rejected' => $rejected,
            'acceptTemplate' => $acceptTemplate,
            'rejectTemplate' => $rejectTemplate
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                foreach($accepted as $participant) {
                    if (in_array($participant['id'],$values['accepted_id'])) {
                        $this->getModelHelper('mail')->send('meetings.accept', array(
                            'to' => $participant['email'],
                            'meeting' => $meeting['title'],
                            'firstname' => $participant['firstname'],
                            'lastname' => $participant['lastname']
                        ));
                    }
                }
                foreach($rejected as $participant) {
                    if (in_array($participant['id'],$values['rejected_id'])) {
                        $this->getModelHelper('mail')->send('meetings.reject', array(
                            'to' => $participant['email'],
                            'meeting' => $meeting['title'],
                            'firstname' => $participant['firstname'],
                            'lastname' => $participant['lastname']
                        ));
                    }
                }

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array(
            'status' => 'form',
            'form' => $form,
            'accpted' => $accepted
        );
    }


}