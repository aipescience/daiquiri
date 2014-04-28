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

class Query_Model_Jobs extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());
    }

    /**
     * Returns the columns of the jobs table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function cols(array $params = array()) {
        // set columns
        $this->_cols = $this->getResource()->fetchCols();

        $cols = array();
        foreach ($this->_cols as $colname) {
            $col = array(
                'name' => ucfirst($colname),
                'sortable' => 'true'
            );
            if ($colname === 'id') {
                $col['width'] = '3em';
            } else if (in_array($colname, array('database', 'table'))) {
                $col['width'] = '16em';
            } else if ($colname === 'time') {
                $col['width'] = '12em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }

        $cols[] = array(
            'name' => 'Options',
            'width' => '12em',
            'sortable' => 'false',
            'search' => 'false'
        );
        
        return array('cols' => $cols, 'status' => 'ok');
    }

    /**
     * Returns the rows of the jobs table specified by some parameters. 
     * @param array $params get params of the request
     * @return array $response
     */
    public function rows(array $params = array()) {
        // set columns
        $this->_cols = $this->getResource()->fetchCols();

        // parse params
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);

        // get the data from the database
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add options
        $rows = array();
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach ($this->_cols as $col) {
                $row[] = $dbRow[$col];
            }

            $options = array(
                $this->internalLink(array(
                    'text' => 'Show',
                    'href' => '/query/jobs/show/id/' . $dbRow['id'],
                    'resource' => 'Query_Model_Jobs',
                    'permission' => 'show'
                ))
            );
            if ($this->getResource()->isStatusKillable($dbRow['status'])) {
                $options[] = $this->internalLink(array(
                    'text' => 'Kill',
                    'href' => '/query/jobs/kill/id/' . $dbRow['id'],
                    'resource' => 'Query_Model_Jobs',
                    'permission' => 'kill'
                ));
            }
            $options[] = $this->internalLink(array(
                'text' => 'Remove',
                'href' => '/query/jobs/remove/id/' . $dbRow['id'],
                'resource' => 'Query_Model_Jobs',
                'permission' => 'remove'
            ));
            $row[] = implode('&nbsp;',$options);

            $rows[] = $row;
        }

        return $this->getModelHelper('pagination')->response($rows, $sqloptions);
    }

    /**
     * Return stored information of a job.
     * @param type $id id of the job
     * @return array $response
     */
    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Kills a job if the query queue supports this.
     * @param type $id id of the job
     * @return array $response
     */
    public function kill($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Kill job'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $this->getResource()->killJob($id);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Remove a job.
     * @param type $id id of the job
     * @return array $response
     */
    public function remove($id, array $formParams = array()) {
        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Remove job'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $this->getResource()->removeJob($id);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
