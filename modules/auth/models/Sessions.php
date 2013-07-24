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

/**
 * Model for the session manegement.
 */
class Auth_Model_Sessions extends Daiquiri_Model_PaginatedTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Sessions');
    }

    /**
     * Returns the main data of the user table.
     * @return array 
     */
    public function rows(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        // create csrf hash for clickjacking protection
        $csrf = md5(rand(0, 100000) + rand(0, 100000));

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add an options to destroy the session
        if (isset($params['options']) && $params['options'] === 'true') {
            for ($i = 0; $i < sizeof($rows); $i++) {
                $session = $rows[$i]['id'];
                $link = $this->internalLink(array(
                    'text' => 'Destroy',
                    'href' => '/auth/sessions/destroy/session/' . $session . '/csrf/' . $csrf,
                    'resource' => 'Auth_Model_Sessions',
                    'permission' => 'destroy'));
                $rows[$i]['options'] = $link;
            }
        }

        return $this->_response($rows, $sqloptions);
    }

    /**
     * Returns the columns of the table.
     * @return array 
     */
    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        foreach ($params['cols'] as $name) {
            $col = array(
                'name' => $name,
                'sortable' => 'true'
            );
            if ($name === 'email') {
                $col['width'] = '18em';
            } else if ($name === 'modified') {
                $col['width'] = '13em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }

        if (isset($params['options']) && $params['options'] === 'true') {
            $cols[] = array(
                'name' => 'options',
                'width' => '8em',
                'sortable' => 'false'
            );
        }
        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Destroys a given session
     * @param string $session
     * @return array
     */
    public function destroy($session) {
        $this->getResource()->deleteRow($session);
        return array('status' => 'ok');
    }

}
