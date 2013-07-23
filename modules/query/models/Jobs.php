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
 * Model for the currently running query jobs.
 */
class Query_Model_Jobs extends Daiquiri_Model_PaginatedTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);
        $this->setResource(get_class($resource));
    }

    /**
     * Returns the messages as rows.
     * @return array 
     */
    public function rows(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
        }

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);

        // loop through the table and add options
        if (isset($params['options']) && $params['options'] === 'true') {
            for ($i = 0; $i < sizeof($rows); $i++) {
                $id = $rows[$i]['id'];

                if (! empty($rows[$i]['status'])) {
                    $status = $rows[$i]['status'];
                } else {
                    $status = "";
                }

                //construct management options
                $links = array($this->internalLink(array(
                        'text' => 'Show',
                        'href' => '/query/jobs/show/id/' . $id,
                        'resource' => 'Query_Model_Jobs',
                        'permission' => 'show')));

                if ($this->getResource()->isStatusKillable($status)) {

                    $links[] = $this->internalLink(array(
                        'text' => 'Kill',
                        'href' => '/query/jobs/kill/id/' . $id,
                        'resource' => 'Query_Model_Jobs',
                        'permission' => 'kill'));
                }

                $links[] = $this->internalLink(array(
                    'text' => 'Remove',
                    'href' => '/query/jobs/remove/id/' . $id,
                    'resource' => 'Query_Model_Jobs',
                    'permission' => 'remove'));

                $rows[$i]['options'] = implode('&nbsp;', $links);
            }
        }

        return $this->_response($rows, $sqloptions);
        ;
    }

    /**
     * Returns the columns to the rows.
     * @return array 
     */
    public function cols(array $params = array()) {
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
        }

        $cols = array();
        foreach ($params['cols'] as $name) {
            $col = array(
                'name' => ucfirst($name),
                'sortable' => 'true'
            );
            if ($name === 'id') {
                $col['width'] = '2em';
                $col['align'] = 'center';
            } else if (in_array($name, array('database', 'table'))) {
                $col['width'] = '16em';
            } else if ($name === 'time') {
                $col['width'] = '12em';
            } else {
                $col['width'] = '8em';
            }
            $cols[] = $col;
        }

        if (isset($params['options']) && $params['options'] === 'true') {
            $cols[] = array(
                'name' => 'Options',
                'width' => '12em',
                'sortable' => 'false',
                'search' => 'false'
            );
        }

        return array('cols' => $cols, 'status' => 'ok');
        ;
    }

    /**
     * Return stored information of a job.
     * @param type $input id OR name of the job
     */
    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Kills a job if the query queue supports this.
     * @param array $param
     * @return status array
     */
    public function kill($id, array $formParams = array()) {
        // create the form object
        $form = new Query_Form_KillJob();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->killJob($id);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Remove a job.
     * @param array $param
     * @return array
     */
    public function remove($id, array $formParams = array()) {
        // create the form object
        $form = new Query_Form_RemoveJob();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->removeJob($id);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
