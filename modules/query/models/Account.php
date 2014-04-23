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

class Query_Model_Account extends Daiquiri_Model_Abstract {

    /**
     * Returns all jobs for the current user.
     * @return array $response
     */
    public function listJobs() {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get the sqloptions needed to show the list of jobs
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // get rows and return
        $rows = array();
        $dbRows = $this->getResource()->fetchRows(array(
            'where' => array('user_id = ?' => $userId)
        ));
        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach (array('id', 'table', 'status') as $col) {
                $row[$col] = $dbRow[$col];
            }
            $rows[] = $row;
        }

        return array('jobs' => $rows, 'status' => 'ok');
    }

    /**
     * Returns one jobs for the current user.
     * @param type $id job id
     * @return array $response
     */
    public function showJob($id) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $dbRow = $this->getResource()->fetchRow($id);
        if (empty($dbRow) || $dbRow['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // fetch table statistics
        $stat = $this->getResource()->fetchTableStats($id);

        // create return array
        $row = array();
        $translations = $this->getResource()->getTranslations();
        foreach (array_merge($dbRow, $stat) as $key => $value) {
            $row[$key] = array(
                'key' => $key,
                'name' => $translations[$key],
                'value' => $value
            );
        }

        // add username
        $row['username'] = array(
            'key' => 'username',
            'name' => 'Username',
            'value' => Daiquiri_Auth::getInstance()->getCurrentUsername()
        );

        return array('job' => $row, 'status' => 'ok');
    }

    /**
     * Renames a job.
     * @param type $id job id
     * @param array $formParams
     * @return array $response
     */
    public function renameJob($id, array $formParams = array()) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->fetchRow($id);
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create the form object
        $form = new Query_Form_RenameJob(array(
            'tablename' => $row['table']
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if table was not renamed at all
                if ($row['table'] === $values['tablename']) {
                    return array('status' => 'ok');
                } else {
                    try {
                        $this->getResource()->renameJob($id, $values['tablename']);
                        return array('status' => 'ok');
                    } catch (Exception $e) {
                        return $this->getModelHelper('CRUD')->validationErrorResponse($form,$e->getMessage());
                    }
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Kills a running job.
     * @param type $id job id
     * @param array $formParams
     * @return array $response
     */
    public function killJob($id, array $formParams = array()) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->fetchRow($id);
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Kill job'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                    try {
                        $this->getResource()->killJob($id);
                        return array('status' => 'ok');
                    } catch (Exception $e) {
                        return $this->getModelHelper('CRUD')->validationErrorResponse($form,$e->getMessage());
                    }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Removes a job.
     * @param type $id job id
     * @param array $formParams
     * @return array $response
     */
    public function removeJob($id, array $formParams = array()) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->fetchRow($id);
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create the form object
        $form = new Daiquiri_Form_Danger(array(
            'submit' => 'Remove job'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                try {
                    $this->getResource()->removeJob($id);
                    return array('status' => 'ok');
                } catch (Exception $e) {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form,$e->getMessage());
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Returns all databases and tables which the user has access to.
     * @return array $response
     */
    public function databases() {
        // get all databases from database model
        $databasesModel = new Data_Model_Databases();
        $rows = array();
        foreach($databasesModel->getResource()->fetchRows() as $row) {
            $database = $databasesModel->getResource()->fetchRow($row['id'], true, true);

            $database['publication_role'] = Daiquiri_Auth::getInstance()->getRole($database['publication_role_id']);
            foreach ($database['tables'] as &$table) {
                $table['publication_role'] = Daiquiri_Auth::getInstance()->getRole($table['publication_role_id']);
            }

            $rows[] = $database;
        }

        // check permissions and build array
        $databases = array();
        foreach ($rows as $database) {

            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($database['publication_role_id'])) {
                $db = array(
                    'id' => $database['id'],
                    'name' => $database['name'],
                    'tables' => array()
                );

                foreach ($database['tables'] as $table) {
                    if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($database['publication_role_id'])) {
                        $t = array(
                            'id' => $table['id'],
                            'name' => $table['name'],
                            'columns' => array(),
                        );

                        foreach ($table['columns'] as $column) {
                            $t['columns'][] = array(
                                'id' => $column['id'],
                                'name' => $column['name']
                            );
                        }
                        $db['tables'][] = $t;
                    }
                }
                $databases[] = $db;
            }
        }

        // get current username and the user db
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $userDbName = Daiquiri_Config::getInstance()->getUserDbName($username);

        // prepare auto increment counters
        $table_id = 1;
        $column_id = 1;

        // prepate userdb array
        $userdb = array(
            'id' => 'userdb',
            'name' => $userDbName,
            'tables' => array()
        );

        // get tables of this database
        $resource = new Data_Model_Resource_Viewer();
        $resource->init($userdb['name']);
        $usertables = $resource->fetchTables();

        // find all the user tables that are currently open and cannot be queried for information
        // get the user adapter
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $userdb['name'] . '` WHERE In_use > 0')->fetchAll();
        foreach ($lockedTables as $table) {
            $key = array_search($table['Table'], $usertables);
            if ($key !== false) {
                unset($usertables[$key]);
            }
        }

        foreach ($usertables as $usertable) {
            $table = array(
                'id' => 'userdb-table-' . $table_id++,
                'name' => $usertable,
                'columns' => array()
            );

            try {
                $resource->init($userdb['name'], $usertable);
            } catch (Exception $e) {
                continue;
            }

            $usercolumns = $resource->fetchCols();
            foreach ($usercolumns as $usercolumn) {
                $table['columns'][] = array(
                    'id' => 'userdb-column-' . $column_id++,
                    'name' => $usercolumn
                );
            }

            $userdb['tables'][] = $table;
        }

        $databases[] = $userdb;
        return array('databases' => $databases, 'status' => 'ok');
    }

    /**
     * Returns all functions which the user has access to.
     * @return array $response
     */
    public function functions() {
        $resource = new Data_Model_Resource_Functions();
        $rows = array();
        foreach ($resource->fetchRows() as $row) {
            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id'])) {
                $row['publication_role'] = Daiquiri_Auth::getInstance()->getRole($row['publication_role_id']);
                $rows[] = $row;
            }
        }
        return array('functions' => $rows, 'status' => 'ok');
    }

    /**
     * Returns all examles which the user has access to.
     * @return array $response
     */
    public function examples() {
        $model = new Query_Model_Examples();
        $rows = array();
        foreach ($model->getResource()->fetchRows() as $row) {
            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($row['publication_role_id'])) {
                $row['publication_role'] = Daiquiri_Auth::getInstance()->getRole($row['publication_role_id']);
                $rows[] = $row;
            }
        }
        return array('examples' => $rows, 'status' => 'ok');
    }

}
