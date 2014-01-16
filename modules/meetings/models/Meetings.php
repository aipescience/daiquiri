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

class Meetings_Model_Meetings extends Daiquiri_Model_CRUD {

    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Meetings');
        $this->_options = array(
            'delete' => array(
                'form' => 'Meetings_Form_Delete',
                'submit' => 'Delete meeting'
            ),
        );
    }

    public function create(array $formParams = array()) {
        // get models
        $contributionTypeModel = new Meetings_Model_ContributionTypes();
        $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();

        // create the form object
        $form = new Meetings_Form_Meeting(array(
            'submit'=> 'Create meeting',
            'contributionTypes' => $contributionTypeModel->getResource()->fetchValues('contribution_type'),
            'participantDetailKeys' => $participantDetailKeysModel->getResource()->fetchValues('key')
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

        // create the form object
        $form = new Meetings_Form_Meeting(array(
            'submit'=> 'Update meeting',
            'contributionTypes' => $contributionTypeModel->getResource()->fetchValues('contribution_type'),
            'participantDetailKeys' => $participantDetailKeysModel->getResource()->fetchValues('key'),
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
}