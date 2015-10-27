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
            'manager' => array(
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
            echo '    Initialising Contact_Status' . PHP_EOL;
            foreach ($this->_init->options['init']['contact']['status'] as $status) {
                $a = array('status' => $status);
                $r = $contactStatusModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create category entries for the contact module
        $contactCategoriesModel = new Contact_Model_Categories();
        if ($contactCategoriesModel->getResource()->countRows() == 0) {
            echo '    Initialising Contact_Categories' . PHP_EOL;
            foreach ($this->_init->options['init']['contact']['categories'] as $category) {
                $a = array('category' => $category);
                $r = $contactCategoriesModel->create($a);
                $this->_check($r, $a);
            }
        }
    }
}
