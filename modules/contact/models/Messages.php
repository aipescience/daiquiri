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

/**
 * Provides methods for everything related to contact form
 */
class Contact_Model_Messages extends Daiquiri_Model_PaginatedTable {

    /**
     * @brief Get message-table and pass on to the view
     */
    protected $_cols = array('id', 'firstname', 'lastname', 'email', 'subject', 'category', 'status');

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Contact_Model_Resource_Messages');
    }

    /**
     * Returns the messages as rows.
     * @return array 
     */
    public function rows(array $params = array()) {
        // get the table from the resource using _showTable
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        }

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);
        $response = $this->_response($rows, $sqloptions);

        // loop through the table and add an options to show the message
        if (isset($params['options']) && $params['options'] === 'true') {
            for ($i = 0; $i < sizeof($response->rows); $i++) {
                $id = $response->rows[$i]['id'];
                $link = $this->internalLink(array(
                    'text' => 'Respond',
                    'href' => '/contact/messages/respond/id/' . $id,
                    'resource' => 'Contact_Model_Messages',
                    'permission' => 'respond'));
                $response->rows[$i]["cell"][] = $link;
            }
        }

        return $response;
    }

    /**
     * Returns the columns to the rows.
     * @return array 
     */
    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        }

        foreach ($this->_cols as $name) {
            $col = array(
                'name' => ucfirst($name),
                'sortable' => 'true'
            );
            if ($name === 'id') {
                $col['width'] = '20px';
                $col['align'] = 'center';
            } else if ($name === 'email') {
                $col['width'] = '150px';
            } else if ($name === 'subject') {
                $col['width'] = '180px';
            } else if ($name === 'category') {
                $col['width'] = '60px';
            } else if ($name === 'status') {
                $col['width'] = '60px';
            } else {
                $col['width'] = '80px';
            }
            $cols[] = $col;
        }
        if (isset($params['options']) && $params['options'] === 'true') {
            $cols[] = array(
                'name' => 'options',
                'width' => '120px',
                'sortable' => 'false'
            );
        }

        return $cols;
    }

    /**
     * @param array $params
     */
    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Reply to a contact fom message
     */
    public function respond($id, array $formParams = array()) {
        // get the message
        $message = $this->getResource()->fetchRow($id);

        // get mail resource and template
        $mailResource = new Contact_Model_Resource_Mail();
        $template = $mailResource->getRespondTemplate($message);

        // create the form object
        $form = new Contact_Form_Respond(array(
                    'subject' => $template['subject'],
                    'body' => $template['body']
                ));

        if (!empty($formParams) && $form->isValid($formParams)) {

            // form is valid, get values
            $values = $form->getValues();
            unset($values['submit']);

            // send mail to user who used the contact form
            $mailResource = new Contact_Model_Resource_Mail();
            $mailResource->sendRespondMail($values, array('email' => $message['email']));

            // set message status to closed
            $statusModel = new Contact_Model_Status();
            $status_id = $statusModel->getId('closed');
            $this->getResource()->updateRow($id, array('status_id' => $status_id));

            return array('form' => null, 'status' => 'ok');
        }

        return array('message' => $message, 'form' => $form, 'status' => 'form');
    }

}