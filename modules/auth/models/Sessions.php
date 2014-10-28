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

class Auth_Model_Sessions extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object and cols.
     */
    public function __construct() {
        $this->setResource('Auth_Model_Resource_Sessions');
        $this->_cols = array('session','modified','username','ip','userAgent');
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
                'name' => $colname,
                'sortable' => 'true'
            );
            if ($colname === 'session') {
                $col['width'] = '14em';
            } else if ($colname === 'userAgent') {
                $col['width'] = '26em';
            } else if ($colname === 'modified') {
                $col['width'] = '12em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }
        $cols[] = array(
            'name' => 'options',
            'width' => '12em',
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
                'permission' => 'destroy',
                'class' => 'daiquiri-admin-option'
            ));

            $rows[] = $row;
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    /**
     * Destroys a given session
     * @param string $session
     * @param array $formParams
     * @return array $response
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
