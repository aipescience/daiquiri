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

class Meetings_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the meetings module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Meetings_Model_Meetings',
            'Meetings_Model_Participants',
            'Meetings_Model_ParticipantDetails',
            'Meetings_Model_ParticipantDetailKeys',
            'Meetings_Model_ParticipantStatus',
            'Meetings_Model_Contributions',
            'Meetings_Model_ContributionTypes',
            'Meetings_Model_Registration'
        );
    }

    /**
     * Returns the acl rules for the meetings module.
     * @return array $rules
     */
    public function getRules() {
        return array(
            'guest' => array(
                'Meetings_Model_Participants' => array('info'),
                'Meetings_Model_Contributions' => array('info'),
                'Meetings_Model_Registration' => array('register','validate')
            ),
            'manager' => array(
                'Meetings_Model_Meetings' => array('index','create','show','update'),
                'Meetings_Model_Participants' => array('index','cols','rows','show','update','delete','accept','reject'),
                'Meetings_Model_Contributions' => array('index','cols','rows','show','update','delete','accept','reject'),
            ),
            'admin' => array(
                'Meetings_Model_Meetings' => array('index','create','show','update','delete','mails'),
                'Meetings_Model_Participants' => array('index','cols','rows','export','create','show','update','delete','accept','reject'),
                'Meetings_Model_ParticipantDetails' => array('index','create','show','update','delete'),
                'Meetings_Model_ParticipantDetailKeys' => array( 'index','create','show','update','delete'),
                'Meetings_Model_ParticipantStatus' => array('index','create','show','update','delete'),
                'Meetings_Model_Contributions' => array('index','cols','rows','export','create','show','update','delete','accept','reject'),
                'Meetings_Model_ContributionTypes' => array('index','create','show','update','delete'),
                'Meetings_Model_Registration' => array('index','delete')
            )
        );
    }

    /**
     * Processes the 'meetings' part of $options['config'].
     */
    public function processConfig() {
        if (!isset($this->_init->input['config']['meetings'])) {
            $input = array();
        } else if (!is_array($this->_init->input['config']['meetings'])) {
            $this->_error('Meetings config options needs to be an array.');
        } else {
            $input = $this->_init->input['config']['meetings'];
        }

        // create default entries
        $defaults = array(
            'validation' => false,
            'autoAccept' => false
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // set options
        $this->_init->options['config']['meetings'] = $output;
    }

    /**
     * Processes the 'meetings' part of $options['init'].
     */
    public function processInit() {
        if (!isset($this->_init->input['init']['meetings'])) {
            $input = array();
        } else if (!is_array($this->_init->input['init']['meetings'])) {
            $this->_error('Meetings init options needs to be an array.');
        } else {
            $input = $this->_init->input['init']['meetings'];
        }

        // create default values
        $defaults = array(
            'contributionTypes' => array('poster','talk'),
            'participantDetailKeys' => array(),
            'participantStatus' => array('organizer', 'invited', 'registered', 'accepted', 'rejected'),
            'meetings' => array()
        );

        // construct init array
        $output = array();
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $input)) {
                if (is_array($input[$key])) {
                    $output[$key] = $input[$key];
                } else {
                    $this->_error("Meetings init option 'meetings.$key' needs to be an array.");
                }
            } else {
                $output[$key] = $value;
            }
        }
        
        $this->_init->options['init']['meetings'] = $output;
    }

    /**
     * Initializes the database with the init data for the meetings module.
     */
    public function init() {
        // create contribution types
        $meetingsContributionTypeModel = new Meetings_Model_ContributionTypes();
        if ($meetingsContributionTypeModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['meetings']['contributionTypes'] as $contributionType) {
                $a = array('contribution_type' => $contributionType);
                $r = $meetingsContributionTypeModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create participant detail keys
        $meetingsParticipantDetailKeyModel = new Meetings_Model_ParticipantDetailKeys();
        if ($meetingsParticipantDetailKeyModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['meetings']['participantDetailKeys'] as $a) {
                $a['type_id'] = array_search($a['type'],Meetings_Model_ParticipantDetailKeys::$types);
                unset($a['type']);

                $r = $meetingsParticipantDetailKeyModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create participant status
        $meetingsParticipantStatusModel = new Meetings_Model_ParticipantStatus();
        if ($meetingsParticipantStatusModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['meetings']['participantStatus'] as $participantStatus) {
                $a = array('status' => $participantStatus);
                $r = $meetingsParticipantStatusModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create meetings
        $meetingsMeetingModel = new Meetings_Model_Meetings();
        if ($meetingsMeetingModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['meetings']['meetings'] as $a) {
                $a['contribution_type_id'] = array();
                foreach($a['contribution_types'] as $contribution_type) {
                    $id = $meetingsContributionTypeModel->getResource()->fetchId(
                        array('where' => array('`contribution_type` = ?' => $contribution_type))
                    );
                    $a['contribution_type_id'][] = $id;
                }
                unset($a['contribution_types']);

                $a['participant_detail_key_id'] = array();
                foreach($a['participant_detail_keys'] as $participant_detail_key) {
                    $id = $meetingsParticipantDetailKeyModel->getResource()->fetchId(
                        array('where' => array('`key` = ?' => $participant_detail_key))
                    );
                    $a['participant_detail_key_id'][] = $id;
                }
                unset($a['participant_detail_keys']);

                $r = $meetingsMeetingModel->create($a);
                $this->_check($r, $a);
            }
        }       
    }
}
