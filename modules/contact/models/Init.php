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

    public function parseOptions(array $options) {

        if (!isset($this->_input_options['contact'])) {
            $input = array();
        } else if (!is_array($this->_input_options['contact'])) {
            $this->_error('Contact options need to be an array.');
        } else {
            $input = $this->_input_options['contact'];
        }

        $defaults = array(
            'status' => array('active', 'closed'),
            'categories' => array('Support', 'Bug'),
        );
        $output = array();

        if (!empty($options['config']['contact'])) {
            foreach ($defaults as $key => $value) {
                if (array_key_exists($key, $input)) {
                    if (is_array($input[$key])) {
                        $output[$key] = $input[$key];
                    } else {
                        $this->_error("Contact option 'contact.$key' needs to be an array.");
                    }
                } else {
                    $output[$key] = $value;
                }
            }
        }

        $options['contact'] = $output;
        return $options;
    }

    public function init(array $options) {
        if ($options['config']['contact']) {
            // create status entries for the contact module
            $contactStatusModel = new Contact_Model_Status();
            if (count($contactStatusModel->getValues()) == 0) {
                foreach ($options['contact']['status'] as $status) {
                    $a = array('status' => $status);
                    $r = $contactStatusModel->create($a);
                    $this->_check($r, $a);
                }
            }

            // create category entries for the contact module
            $contactCategoriesModel = new Contact_Model_Categories();
            if (count($contactCategoriesModel->getValues()) == 0) {
                foreach ($options['contact']['categories'] as $category) {
                    $a = array('category' => $category);
                    $r = $contactCategoriesModel->create($a);
                    $this->_check($r, $a);
                }
            }
        }
    }

}

