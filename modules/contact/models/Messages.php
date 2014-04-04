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

class Contact_Model_Messages extends Daiquiri_Model_Table {

    public function __construct() {
        $this->setResource('Contact_Model_Resource_Messages');
        $this->_cols = array('id','firstname','lastname','email','subject','datetime','category','status');
    }

    public function rows(array $params = array()) {
        // get the data from the database
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add an options to show the message
        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->_cols as $col) {
                $row[] = $dbRow[$col];
            }

            $row[] = $this->internalLink(array(
                'text' => 'Respond',
                'href' => '/contact/messages/respond/id/' . $dbRow['id'],
                'resource' => 'Contact_Model_Messages',
                'permission' => 'respond'));

            $rows[] = $row;
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        foreach ($this->_cols as $name) {
            $col = array(
                'name' => ucfirst($name),
                'sortable' => 'true'
            );
            if ($name === 'id') {
                $col['width'] = '3em';
                $col['align'] = 'center';
            } else if ($name === 'email') {
                $col['width'] = '15em';
            } else if ($name === 'subject') {
                $col['width'] = '18em';
            }else if ($name === 'datetime') {
                $col['width'] = '12em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }
        $cols[] = array(
            'name' => 'Options',
            'width' => '6em',
            'sortable' => 'false'
        );
        
        return array('cols' => $cols, 'status' => 'ok');
    }

    public function respond($id, array $formParams = array()) {
        // get the message
        $message = $this->getResource()->fetchRow($id);

        // get the mail template
        $template = $this->getModelHelper('mail')->template('contact.respond');

        // create the form object
        $form = new Contact_Form_Respond(array(
            'subject' => $template['subject'],
            'body' => $template['body']
        ));

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get values
                $values = $form->getValues();
                unset($values['submit']);

                // send mail to user who used the contact form
                $this->getModelHelper('mail')->send('contact.respond', array(
                    'to' => $message['email'],
                    'firstname' => $message['firstname'],
                    'lastname' => $message['lastname'],
                    'subject' => $message['subject']
                ), array(
                    'subject' => $values['subject'],
                    'body' => $values['body']
                ));

                // set message status to closed
                $statusModel = new Contact_Model_Status();
                $status_id = $statusModel->getResource()->fetchId(array(
                    'where' => array('`status` = "closed"')
                ));
                $this->getResource()->updateRow($id, array('status_id' => $status_id));

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'form' => $form
                );
            }
        }

        return array('message' => $message, 'form' => $form, 'status' => 'form');
    }

}