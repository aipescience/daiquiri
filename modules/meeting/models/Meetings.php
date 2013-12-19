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

class Meeting_Model_Meetings extends Daiquiri_Model_SimpleTable {

    public function __construct() {
        $this->setResource('Meeting_Model_Resource_Meetings');
        $this->setValueField('title');
    }

    public function index() {
        return $this->getResource()->fetchRows();
    }

    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    public function create(array $formParams = array()) {
        // create the form object
        $form = new Meeting_Form_Meetings(array(
            'submit' => 'Create new Meeting'
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
        $meeting = $this->getResource()->fetchRow($id);
        if (empty($meeting)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $form = new Meeting_Form_Meetings(array(
            'submit' => 'Create new Meeting',
            'meeting' => $meeting
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

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