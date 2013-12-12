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

class Query_Model_Init extends Daiquiri_Model_Init {

    public function parseOptions(array $options) {
        if (!isset($this->_input_options['query'])) {
            $input = array();
        } else if (!is_array($this->_input_options['query'])) {
            $this->_error('Auth options need to be an array.');
        } else {
            $input = $this->_input_options['query'];
        }

        $output = array();

        // construct examples array
        $output['examples'] = array();
        if (isset($input['examples'])) {
            if (is_array($input['examples'])) {
                $output['examples'] = $input['examples'];
            } else {
                $this->_error("Query option 'examples' needs to be an array.");
            }
        }

        $options['query'] = $output;
        return $options;
    }

    public function init(array $options) {
        // get role model
        $authRoleModel = new Auth_Model_Roles();

        // create config entries
        $queryExamplesModel = new Query_Model_Examples();
        if (count($queryExamplesModel->index()) == 0) {
            foreach ($options['query']['examples'] as $a) {
                $a['publication_role_id'] = $authRoleModel->getId($a['publication_role']);
                unset($a['publication_role']);

                $r = $queryExamplesModel->create($a);
                $this->_check($r, $a);
            }
        }
    }
}
