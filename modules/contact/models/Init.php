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

class Contact_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the contact module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Contact_Model_Submit',
            'Contact_Model_Messages'
        );
    }

    /**
     * Returns the acl rules for the contact module.
     * @return array $rules
     */
    public function getRules() {
        return array(
            'guest' => array(
                'Contact_Model_Submit' => array('contact')
            ),
            'support' => array(
                'Contact_Model_Messages' => array('rows','cols','show','respond')
            )
        );
    }

    /**
     * Processes the 'contact' part of $options['config'].
     */
    public function processConfig() {
        if (isset($this->_init->input['config']['contact'])) {
            $this->_error('No config options for the contact module are supported.');
        }
    }

    /**
     * Processes the 'contact' part of $options['init'].
     */
    public function processInit() {
        if (!isset($this->_init->input['init']['contact'])) {
            $input = array();
        } else if (!is_array($this->_init->input['init']['contact'])) {
            $this->_error('Contact init options need to be an array.');
        } else {
            $input = $this->_init->input['init']['contact'];
        }

        // create default values
        $defaults = array(
            'status' => array('active', 'closed'),
            'categories' => array('Support', 'Bug'),
        );

        // construct init array
        $output = array();
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $input)) {
                if (is_array($input[$key])) {
                    $output[$key] = $input[$key];
                } else {
                    $this->_error("Contact init option 'contact.$key' needs to be an array.");
                }
            } else {
                $output[$key] = $value;
            }
        }
        
        $this->_init->options['init']['contact'] = $output;
    }

    /**
     * Initializes the database with the init data for the contact module.
     */
    public function init() {
        // create status entries for the contact module
        $contactStatusModel = new Contact_Model_Status();
        if ($contactStatusModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['contact']['status'] as $status) {
                $a = array('status' => $status);
                $r = $contactStatusModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create category entries for the contact module
        $contactCategoriesModel = new Contact_Model_Categories();
        if ($contactCategoriesModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['contact']['categories'] as $category) {
                $a = array('category' => $category);
                $r = $contactCategoriesModel->create($a);
                $this->_check($r, $a);
            }
        }
    }
}
