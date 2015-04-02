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

class Meetings_Model_Participants extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource and columns.
     */
    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Participants');
        $this->_cols = array('id','firstname','lastname','email','status');
    }

    /**
     * Returns the public information about a meetings contributions
     * @param string $slug slug of the meeting
     * @return array $response
     */
    public function info($slug) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['participants_publication_role_id'])) {
            return array(
                'status' => 'forbidden',
                'message' => $meeting['participants_message']
            );
        } else {
            return array(
                'status' => 'ok',
                'message' => $meeting['participants_message'],
                'rows' => $this->getResource()->fetchRows(
                    array(
                        'where' => array(
                            '`meeting_id` = ?' => $meeting['id'],
                            '(`status` = "accepted") OR (`status` = "organizer") OR (`status` = "invited")'
                        ),
                        'order' => 'lastname ASC'
                    )
                )
            );
        }
    }

    /**
     * Returns the information about a meetings participants in a convenient text-only format
     * @param string $slug slug of the meeting
     * @param string $status display only participants with a certain status
     * @return array $response
     */
    public function export($slug, $status = false) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get status model
        $participantStatusModel = new Meetings_Model_ParticipantStatus();
        $participantStatus = $participantStatusModel->getResource()->fetchValues('status');

        $where = array('`meeting_id` = ?' => $meeting['id']);
        if (in_array($status, $participantStatus)) {
            $where['`status` = ?'] = $status;
        }

        $dbRows = $this->getResource()->fetchRows(array('where' => $where, 'order' => 'lastname ASC'));

        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = $this->getResource()->fetchRow($dbRow['id']);

            foreach($meeting['participant_detail_keys'] as $d) {
                if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('radio','select'))) {
                    $options = Zend_Json::decode($d['options']);
                    $row['details'][$d['key']] = $options[$row['details'][$d['key']]];
                } else if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('checkbox','multiselect'))) {
                    $options = Zend_Json::decode($d['options']);

                    $values = array();
                    foreach (Zend_Json::decode($row['details'][$d['key']]) as $value_id) {
                        $values[] = $options[$value_id];
                    }

                    $row['details'][$d['key']] = $values;
                }
            }

            $rows[] = $row;
        }

        $keys = array('firstname','lastname','affiliation','email','arrival','departure','status');
        $detailKeys = array();
        foreach($meeting['participant_detail_keys'] as $d) {
            $detailKeys[] = $d['key'];
        }
        $contributionKeys = array();
        foreach ($meeting['contribution_types'] as $contibutionType) {
            $contributionKeys[] = $contibutionType . '_title';
            $contributionKeys[] = $contibutionType . '_abstract';
        }

        return array(
            'status' => 'ok',
            'rows' => $rows,
            'keys' => $keys,
            'detailKeys' => $detailKeys,
            'contributionKeys' => $contributionKeys
        );

    }

    /**
     * Returns the columns of the participants table specified by some parameters.
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        if (empty($params['meetingId'])) {
            $this->_cols[] = 'meeting_title';
        }

        $cols = array();
        foreach($this->_cols as $colname) {
            $col = array('name' => str_replace('_',' ',$colname));
            if ($colname === 'id') {
                $col['width'] = 40;
            } else {
                $col['width'] = 100;
            }
            $col['sortable'] = true;
            $cols[] = $col;
        }
        $cols[] = array('name' => 'options', 'sortable' => false, 'width' => 200);

        return array('status' => 'ok', 'cols' => $cols);
    }

    /**
     * Returns the rows of the participants table specified by some parameters.
     * @param array $params get params of the request
     * @return array $response
     */
    public function rows(array $params = array()) {
        if (empty($params['meetingId'])) {
            $this->_cols[] = 'meeting_title';
        }

        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);

        if (!empty($params['meetingId'])) {
            $sqloptions['where'] = array('`meeting_id` = ?' => $params['meetingId']);
        }

        // get the data from the database
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->_cols as $col) {
                $row[] = $dbRow[$col];
            }

            $options = array();
            foreach (array('show','update','delete') as $option) {
                $options[] = $this->internalLink(array(
                    'text' => ucfirst($option),
                    'href' => '/meetings/participants/' . $option . '/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => $option,
                    'class' => 'daiquiri-admin-option'
                ));
            }
            if (in_array($dbRow['status'], array('registered','rejected'))) {
                $options[] = $this->internalLink(array(
                    'text' => 'Accept',
                    'href' => '/meetings/participants/accept/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => 'accept',
                    'class' => 'daiquiri-admin-option'
                ));
            }
            if (in_array($dbRow['status'], array('registered','accepted'))) {
                $options[] = $this->internalLink(array(
                    'text' => 'Reject',
                    'href' => '/meetings/participants/reject/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Participants',
                    'permission' => 'reject',
                    'class' => 'daiquiri-admin-option'
                ));
            }

            // merge to table row
            $rows[] = array_merge($row, array(implode('&nbsp;',$options)));
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    /**
     * Returns one specific participant.
     * @param int $id id of the participant
     * @return array $response
     */
    public function show($id) {
        $row = $this->getResource()->fetchRow($id);

        // get meeting model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($row['meeting_id']);

        foreach($meeting['participant_detail_keys'] as $d) {
            if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('radio','select'))) {
                $options = Zend_Json::decode($d['options']);
                $row['details'][$d['key']] = $options[$row['details'][$d['key']]];
            } else if (in_array(Meetings_Model_ParticipantDetailKeys::$types[$d['type_id']], array('checkbox','multiselect'))) {
                $options = Zend_Json::decode($d['options']);

                $values = array();
                foreach (Zend_Json::decode($row['details'][$d['key']]) as $value_id) {
                    $values[] = $options[$value_id];
                }

                $row['details'][$d['key']] = $values;
            }
        }

        return array('status' => 'ok', 'row' => $row);
    }

    /**
     * Creates a new participant.
     * @param string $slug slug of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function create($slug, array $formParams = array()) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        $participantStatusModel = new Meetings_Model_ParticipantStatus();
        $participantStatus = $participantStatusModel->getResource()->fetchValues('status');

        // create the form object
        $form = new Meetings_Form_Participants(array(
            'submit'=> 'Create participant',
            'meeting' => $meeting,
            'status' => $participantStatus
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();
                $values['meeting_id'] = $meeting['id'];

                // process the details
                $values['details'] = array();
                foreach ($meeting['participant_detail_keys'] as $detailKey) {
                    if (is_array($values[$detailKey['key']])) {
                        $values['details'][$detailKey['id']] = Zend_Json::encode($values[$detailKey['key']]);
                    } else if ($values[$detailKey['key']] === null) {
                        $values['details'][$detailKey['id']] = Zend_Json::encode(array());
                    } else {
                        $values['details'][$detailKey['id']] = $values[$detailKey['key']];
                    }
                    unset($values[$detailKey['key']]);
                }

                $values['contributions'] = array();
                foreach ($meeting['contribution_types'] as $contributionTypeId => $contributionType) {
                    if ($values[$contributionType . '_bool'] === '1') {
                        $values['contributions'][$contributionTypeId] = array(
                            'title' => $values[$contributionType . '_title'],
                            'abstract' => $values[$contributionType . '_abstract'],
                        );
                    } else {
                        $values['contributions'][$contributionTypeId] = false;
                    }
                    unset($values[$contributionType . '_bool']);
                    unset($values[$contributionType . '_title']);
                    unset($values[$contributionType . '_abstract']);
                }

                // store the values in the database
                $this->getResource()->insertRow($values);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an participant.
     * @param int $id id of the participant
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        // get participant from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get the meeting
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow($entry['meeting_id']);
        $participantStatusModel = new Meetings_Model_ParticipantStatus();
        $participantStatus = $participantStatusModel->getResource()->fetchValues('status');

        // create the form object
        $form = new Meetings_Form_Participants(array(
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

                // process the details
                $values['details'] = array();
                foreach ($meeting['participant_detail_keys'] as $detailKey) {
                    if (is_array($values[$detailKey['key']])) {
                        $values['details'][$detailKey['id']] = Zend_Json::encode($values[$detailKey['key']]);
                    } else if ($values[$detailKey['key']] === null) {
                        $values['details'][$detailKey['id']] = Zend_Json::encode(array());
                    } else {
                        $values['details'][$detailKey['id']] = $values[$detailKey['key']];
                    }
                    unset($values[$detailKey['key']]);
                }

                $values['contributions'] = array();
                foreach ($meeting['contribution_types'] as $contributionTypeId => $contributionType) {
                    if ($values[$contributionType . '_bool'] === '1') {
                        $values['contributions'][$contributionTypeId] = array(
                            'title' => $values[$contributionType . '_title'],
                            'abstract' => $values[$contributionType . '_abstract'],
                        );
                    } else {
                        $values['contributions'][$contributionTypeId] = false;
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a participant.
     * @param int $id id of the participant
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Accepts a participant.
     * @param int $id id of the participant
     * @param array $formParams
     * @return array $response
     */
    public function accept($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Rejects a participant.
     * @param int $id id of the participant
     * @param array $formParams
     * @return array $response
     */
    public function reject($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }
}