<?php

/*  
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

class Meetings_Model_Contributions extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource and columns.
     */
    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Contributions');
        $this->_cols = array('id','title','contribution_type','participant_firstname','participant_lastname','accepted');
        // Note: only 'title','type','participant','accepted' are actually visible
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

        if (!Daiquiri_Auth::getInstance()->checkPublicationRoleId($meeting['contributions_publication_role_id'])) {
            return array(
                'status' => 'forbidden',
                'message' => $meeting['contributions_message']
            );
        } else {
            $dbRows = $this->getResource()->fetchRows(array('where' => array(
                '`meeting_id` = ?' => $meeting['id'],
                '`accepted` = 1'
            )));

            $rows = array();
            foreach($dbRows as $dbRow) {
                if (!array_key_exists($dbRow['contribution_type'], $rows)) {
                    $rows[$dbRow['contribution_type']] = array();
                }
                $rows[$dbRow['contribution_type']][] = $dbRow;
            }

            return array(
                'status' => 'ok',
                'message' => $meeting['contributions_message'],
                'rows' => $rows
            );
        }
    }

    /**
     * Returns the information about a meetings contributions in a convenient text-only format
     * @param string $slug slug of the meeting
     * @param string $status display only contributions of a certain status
     * @param string $contributionType display only contributions of a certain type
     * @return array $response
     */
    public function export($slug, $status = false, $contributionType = false) {
        // get model
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        $where = array('`meeting_id` = ?' => $meeting['id']);
        if (isset($contributionType)) {
            $where[$this->getResource()->quoteIdentifier('Meetings_ContributionTypes','contribution_type') . '=?'] = $contributionType;
        }
        if ($status == 'accepted') {
            $where[] = '`accepted` = 1';
        } else if ($status == 'rejected') {
            $where[] = '`accepted` = 0';
        }

        $dbRows = $this->getResource()->fetchRows(array('where' => $where));

        $rows = array();
        foreach($dbRows as $dbRow) {
            if (!array_key_exists($dbRow['contribution_type'], $rows)) {
                $rows[$dbRow['contribution_type']] = array();
            }
            $rows[$dbRow['contribution_type']][] = $dbRow;
        }

        return array(
            'status' => 'ok',
            'rows' => $rows
        );
        
    }   

    /**
     * Returns the columns of the contributions table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        if (empty($params['meetingId'])) {
            $this->_cols[] = 'meeting_title';
        }

        $cols = array();
        foreach(array('id','title','type','participant','accepted') as $colname) {
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
        return array(
            'status' => 'ok',
            'cols' => $cols
        );
    }

    /**
     * Returns the rows of the contributions table specified by some parameters. 
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

            // rows create manually: Title, Type, Participant, Accepted
            $row[] = $dbRow['id'];
            $row[] = $dbRow['title'];
            $row[] = $dbRow['contribution_type'];
            $row[] = $dbRow['participant_lastname'] . ', ' . $dbRow['participant_firstname'];
            $row[] = (bool) $dbRow['accepted'];

            $options = array();
            foreach (array('show','update','delete') as $option) {
                $options[] = $this->internalLink(array(
                    'text' => ucfirst($option),
                    'href' => '/meetings/contributions/' . $option . '/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Contributions',
                    'permission' => $option,
                    'class' => 'daiquiri-admin-option'
                ));
            }
            if ($dbRow['accepted'] == '0') {
                $options[] = $this->internalLink(array(
                    'text' => 'Accept',
                    'href' => '/meetings/contributions/accept/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Contributions',
                    'permission' => 'accept',
                    'class' => 'daiquiri-admin-option'
                ));
            }
            if ($dbRow['accepted'] == '1') {
                $options[] = $this->internalLink(array(
                    'text' => 'Reject',
                    'href' => '/meetings/contributions/reject/id/' . $dbRow['id'],
                    'resource' => 'Meetings_Model_Contributions',
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
     * Returns one specific contribution.
     * @param int $id id of the contribution
     * @return array $response
     */
    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Creates a new contribution.
     * @param string $slug slug of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function create($slug, array $formParams = array()) {
        // get models
        $meetingsModel = new Meetings_Model_Meetings();
        $meeting = $meetingsModel->getResource()->fetchRow(array(
            'where' => array('slug = ?' => $slug)
        ));

        if (empty($meeting)) {
            throw new Daiquiri_Exception_NotFound();
        }

        $participantsModel = new Meetings_Model_Participants();

        // create the form object
        $form = new Meetings_Form_Contributions(array(
            'submit'=> 'Create contribution',
            'meeting' => $meeting,
            'participants' => $participantsModel->getResource()->fetchValues('email', $meeting['id'])
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an contribution.
     * @param int $id id of the contribution
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
        $participantsModel = new Meetings_Model_Participants();

        // create the form object
        $form = new Meetings_Form_Contributions(array(
            'submit'=> 'Update participant',
            'meeting' => $meetingsModel->getResource()->fetchRow($entry['meeting_id']),
            'participants' => $participantsModel->getResource()->fetchValues('email', $entry['meeting_id']),
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }
    
    /**
     * Deletes a contribution.
     * @param int $id id of the contribution
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

    /**
     * Accepts a contribution.
     * @param int $id id of the contribution
     * @param array $formParams
     * @return array $response
     */
    public function accept($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Accept the contribution'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $this->getResource()->updateRow($id, array('accepted' => 1));

                // also accept participant
                $contribution = $this->getResource()->fetchRow($id);
                $participantModel = new Meetings_Model_Participants();
                $participantModel->accept($contribution['participant_id']);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Rejects a contribution.
     * @param int $id id of the contribution
     * @param array $formParams
     * @return array $response
     */
    public function reject($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Confirm(array(
            'submit' => 'Reject the contribution'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the user credentials
                $this->getResource()->updateRow($id, array('accepted' => 0));

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}