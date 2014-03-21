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

class Auth_Model_Sessions extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and cols.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Sessions');
        $this->_cols = array('session','username','ip','userAgent','modified');
    }

    /**
     * Returns the columns of the session table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        $cols = array();
        foreach ($this->_cols as $colname) {
            $col = array(
                'name' => ucfirst($colname),
                'sortable' => 'true'
            );
            if ($colname === 'email') {
                $col['width'] = '18em';
            } else if ($colname === 'modified') {
                $col['width'] = '13em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }
        $cols[] = array(
            'name' => 'Options',
            'width' => '8em',
            'sortable' => 'false'
        );
        
        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Returns the rows of the session table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function rows(array $params = array()) {
        // parse params
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);

        // get the data from the database
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add an options to destroy the session
        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->_cols as $col) {
                $row[] = $dbRow[$col];
            }

            $row[] = $this->internalLink(array(
                'text' => 'Destroy',
                'href' => '/auth/sessions/destroy/session/' . $dbRow['session'],
                'resource' => 'Auth_Model_Sessions',
                'permission' => 'destroy'
            ));

            $rows[] = $row;
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    /**
     * Destroys a given session
     * @param string $session
     * @param array $formParams
     * @return array
     */
    public function destroy($session, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Destroy session'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $this->getResource()->deleteRow($session);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }
}
