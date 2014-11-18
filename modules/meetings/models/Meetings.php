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

class Meetings_Model_Meetings extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Meetings_Model_Resource_Meetings');
    }

    /**
     * Returns all meetings
     * @return array $response
     */
    public function index() {
        $rows = $this->getResource()->fetchRows();
        return array('status' => 'ok', 'rows' => $rows);
    }

    /**
     * Returns one specific meeting.
     * @param mixed $input int id or array with "slug" key
     * @return array $response
     */
    public function show($input) {
        if (is_int($input)) {
            $row = $this->getResource()->fetchRow($input);
        } elseif (is_array($input)) {
            if (empty($input['slug'])) {
                throw new Exception('Either int id or array with "slug" key must be provided as $input');
            }
            $row = $this->getResource()->fetchRow(array(
                'where' => array('slug = ?' => $input['slug'])
            ));
        } else {
            throw new Exception('$input has wrong type.');
        }

        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

        $siteUrl = Daiquiri_Config::getInstance()->getSiteUrl();

        $row['public_registration_page'] = $siteUrl . '/meetings/' . $row['slug'] . '/registration/';
        $row['public_participants_page'] = $siteUrl . '/meetings/' . $row['slug'] . '/info/participants/';
        $row['public_contributions_page'] = $siteUrl . '/meetings/' . $row['slug'] . '/info/contributions/';


        return array('status' => 'ok','row' => $row);
    }

    /**
     * Creates a new meeting.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // get models
        $contributionTypeModel = new Meetings_Model_ContributionTypes();
        $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());
        
        // create the form object
        $form = new Meetings_Form_Meetings(array(
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates an meeting.
     * @param int $id id of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function update($id , array $formParams = array()) {
        // get meeting from teh database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get models
        $contributionTypeModel = new Meetings_Model_ContributionTypes();
        $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();

        // get roles
        $roles = array_merge(array(0 => 'not published'), Daiquiri_Auth::getInstance()->getRoles());

        // create the form object
        $form = new Meetings_Form_Meetings(array(
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
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a meeting.
     * @param int $id id of the meeting
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }

}