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

class Query_Model_Jobs extends Daiquiri_Model_Table {

    /**
     * Constructor. Sets resource object.
     */
    public function __construct() {
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        $this->_cols = array('id','database','table',$this->getResource()->getTimeField(),'status');
        $resourceClass = get_class($this->getResource());
        if ($resourceClass::$hasQueues) {
            $this->_cols[] = 'queue';
        } 
    }

    /**
     * Returns the columns of the jobs table specified by some parameters. 
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
            if ($colname === 'id') {
                $col['width'] = '120px';
            } else if (in_array($colname, array('database', 'table'))) {
                $col['width'] = '120px';
            } else if ($colname === 'username') {
                $col['width'] = '60px';
            } else if (in_array($colname, array('queue', 'status'))) {
                $col['width'] = '40px';
            } else {
                $col['width'] = '100px';
            }
            $cols[] = $col;
        }

        $cols[] = array(
            'name' => 'Options',
            'width' => '100px',
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
