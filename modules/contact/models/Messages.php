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

class Contact_Model_Messages extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and cols.
     */
    public function __construct() {
        $this->setResource('Contact_Model_Resource_Messages');
        $this->_cols = array('id','firstname','lastname','email','subject','datetime','category','status');
    }

    /**
     * Returns the columns of the message table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->_cols;
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        foreach ($this->_cols as $colname) {
            $col = array(
                'name' => $colname,
                'sortable' => true
            );
            if ($colname === 'id') {
                $col['width'] = 45;
            } else if ($colname === 'email') {
                $col['width'] = 180;
            } else if ($colname === 'datetime') {
                $col['width'] = 140;
            } else if ($colname === 'category') {
                $col['width'] = 100;
            } else if ($colname === 'status') {
                $col['width'] = 80;
            } else {
                $col['width'] = 100;
            }
            $cols[] = $col;
        }
        $cols[] = array(
            'name' => 'options',
            'width' => '6em',
            'sortable' => 'false'
        );
        
        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Returns the rows of the message table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
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

    /**
     * Responds to a contact message.
     * @param int $id id of the message
     * @param array $formParams
     * @return array $response
     */
    public function respond($id, array $formParams = array()) {
        // get the message
        $message = $this->getResource()->fetchRow($id);

        // create the form object
        $form = new Contact_Form_Respond(array(
            'subject' => "Re: {$message['subject']}",
            'body' => "Dear {$message['firstname']} {$message['lastname']},\n\n\n\nBest Regards"
        ));

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get values
                $values = $form->getValues();
                unset($values['submit']);

                // get manager
                $userResource = new Auth_Model_Resource_User();
                $manager = array_merge(
                    $userResource->fetchEmailByRole('admin'),
                    $userResource->fetchEmailByRole('manager')
                );

                // send mail to user who used the contact form
                $this->getModelHelper('mail')->send('contact.respond', array(
                    'to' => $message['email'],
                    'bcc' => $manager,
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