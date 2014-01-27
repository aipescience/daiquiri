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

class Meetings_Model_Participants extends Daiquiri_Model_CRUD {

    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Participants');
        $this->_options = array(
            'delete' => array(
                'form' => 'Meetings_Form_Delete',
                'submit' => 'Delete Participant'
            ),
        );
    }

    public function getCols() {
        return array('firstname','lastname','email','status');
    }

    public function index($meetingId) {
        if ($meetingId === null) {
            return array(
                'status' => 'ok',
                'rows' => $this->getResource()->fetchRows(),
                'meeting' => null
            );
        } else {
            $meetingsModel = new Meetings_Model_Meetings();
            return array(
                'status' => 'ok',
                'rows' => $this->getResource()->fetchRows(
                    array('where' => array('`meeting_id` = ?' => $meetingId))),
                'meeting' => $meetingsModel->getResource()->fetchRow($meetingId)
            );
        }
    }

    public function cols() {
        $cols = array();

        foreach($this->_cols as $colname) {
            $col = array('name' => ucfirst($colname));
            $cols[] = $col;
        }

        $cols[] = array(
            'name' => 'Options',
            'sortable' => 'false'
        );

        return array(
            'status' => 'ok',
            'cols' => $cols
        );
    }

    public function rows(array $params = array()) {
        $pagination = new Daiquiri_Model_Pagination($this);
        $sqloptions = $pagination->sqloptions($params);

        // get the data from the database
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->getCols() as $col) {
                $row[] = $dbRow[$col];
            }

            $options = array();
            foreach (array('show','update','delete') as $option) {
                $options[] = $this->internalLink(array(
                    'text' => ucfirst($option),
                    'href' => '/meetings/participants/' . $option . '/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => $option
                ));
            }
            if (in_array($dbRow['status'], array('registered','rejected'))) {
                $options[] = $this->internalLink(array(
                    'text' => 'Accept',
                    'href' => '/meetings/participants/accept/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => 'accept'
                ));
            }
            if (in_array($dbRow['status'], array('registered','accepted'))) {
                $options[] = $this->internalLink(array(
                    'text' => 'Reject',
                    'href' => '/meetings/participants/reject/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => 'reject'
                ));
            }

            // merge to table row
            $rows[] = array_merge($row, array(implode('&nbsp;',$options)));
        }

        return $pagination->response($rows, $sqloptions);
    }

    public function info($meetingId) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($meetingId);

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['participants_publication_role_id'])) {
            return array(
                'status' => 'error'
            );
        } else {
            return array(
                'status' => 'ok',
                'data' => $this->getResource()->fetchRows(
                    array(
                        'where' => array(
                            '`meeting_id` = ?' => $meetingId,
                            '(`status` = "accepted") OR (`status` = "organizer") OR (`status` = "invited")'
                        )
                    )
                )
            );
        }
    }

    public function create($meetingId, array $formParams = array()) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($meetingId);
        $participantStatusModel = new Meetings_Model_ParticipantStatus();
        $participantStatus = $participantStatusModel->getResource()->fetchValues('status');

        // create the form object
        $form = new Meetings_Form_Participant(array(
            'submit'=> 'Create participant',
            'meeting' => $meeting,
            'status' => $participantStatus
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

                    //$values['details'][$key_id] = $values[$key];
                    unset($values[$contributionType . '_bool']);
                    unset($values[$contributionType . '_title']);
                    unset($values[$contributionType . '_abstract']);
                }

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

    public function update($id, array $formParams = array()) {
        // get participant from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // get the meeting
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($entry['meeting_id']);
        $participantStatusModel = new Meetings_Model_ParticipantStatus();
        $participantStatus = $participantStatusModel->getResource()->fetchValues('status');

        // create the form object
        $form = new Meetings_Form_Participant(array(
            'submit'=> 'Update participant',
            'meeting' => $meeting,
            'entry' => $entry,
            'status' => $participantStatus
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();
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

                    //$values['details'][$key_id] = $values[$key];
                    unset($values[$contributionType . '_bool']);
                    unset($values[$contributionType . '_title']);
                    unset($values[$contributionType . '_abstract']);
                }

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

    public function accept($id, array $formParams = array()) {
        // create the form object
        $form = new Meetings_Form_AcceptReject(array(
            'submit' => 'Accept the participant'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the accepted status
                $participantStatusModel = new Meetings_Model_ParticipantStatus();
                $status_id = $participantStatusModel->getResource()->fetchId(array(
                    'where' => array('`status` = "accepted"')
                ));

                // get the user credentials
                $participant = $this->getResource()->updateRow($id, array('status_id' => $status_id));

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function reject($id, array $formParams = array()) {
        // create the form object
        $form = new Meetings_Form_AcceptReject(array(
            'submit' => 'Reject the participant'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the rejected status
                $participantStatusModel = new Meetings_Model_ParticipantStatus();
                $status_id = $participantStatusModel->getResource()->fetchId(array(
                    'where' => array('`status` = "rejected"')
                ));

                // get the user credentials
                $participant = $this->getResource()->updateRow($id, array('status_id' => $status_id));

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }


}