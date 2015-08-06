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

class Query_Model_Account extends Daiquiri_Model_Abstract {

    /**
     * Returns the message, the database information, and the jobs for the current user.
     * @return array $response
     */
    public function index() {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get the current query message
        $messagesModel = new Core_Model_Messages();
        $row = $messagesModel->getResource()->fetchRow(array(
            'where' => array('`key` = "query"')
        ));
        if (empty($row)) {
            $message = false;
        } else {
            $message = $row['value'];
        }

        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // get groups
        $groupsResource = new Query_Model_Resource_Groups();
        $groups = $groupsResource->fetchRows(array(
            'where' => array(
                'user_id = ?' => $userId,
            )
        ));

        // get rows
        try {
            $rows = $this->getResource()->fetchRows(array(
                'where' => array(
                    'user_id = ?' => $userId,
                    'removed = ?' => 0
                )
            ));
        } catch (Exception $e) {
            $rows = array();
        }

        // get number of currently active jobs
        $nactive = $this->getResource()->fetchNActive();

        // check if guest or not
        $guest = (Daiquiri_Auth::getInstance()->getCurrentRole() === 'guest');

        // get the quota information
        $role = Daiquiri_Auth::getInstance()->getCurrentRole();

        // get the quota space
        $stats =  $this->getResource()->fetchStats($userId);
        $quota = array(
            'used' => $stats['size'],
            'max' => Daiquiri_Config::getInstance()->getQueryQuota($role),
        );

        if ($quota['used'] > $quota['max']) {
            $quota['exceeded'] = true;
        } else {
            $quota['exceeded'] = false;
        }

        return array(
            'status' => 'ok',
            'groups' => $groups,
            'jobs' => $rows,
            'database' => array(
                'message' => $message,
                'nactive' => $nactive,
                'guest' => $guest,
                'quota' => $quota
            )
        );
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
        if (empty($dbRow)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($dbRow['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create return array
        $job = array(
            'additional' => array()
        );

        foreach(array('id','database','table','status','error','username','time','timeQueue','timeQuery','nrows','size') as $key) {
            if (isset($dbRow[$key])) {
                $job[$key] = $dbRow[$key];
            }
            unset($dbRow[$key]);
        }

        // extract query, throw away the plan, if one is there
        $queryArray = explode('-- The query plan used to run this query: --',$dbRow['query']);
        $job['query'] = $queryArray[0];
        unset($dbRow['query']);

        // format plan
        if (isset($queryArray[1])) {
            $plan = str_replace("--------------------------------------------\n--\n--",'',$queryArray[1]);
            $plan = trim($plan);
            $plan = str_replace("\n-- ",";\n",$plan);
            $job['plan'] = $plan;
        }

        // get actual query if there is one
        if (isset($dbRow['actualQuery'])) {
            $job['actualQuery'] = str_replace("; ",";\n",$dbRow['actualQuery']);
            unset($dbRow['actualQuery']);
        }

        // create additional array
        $translations = $this->getResource()->getTranslations();
        foreach ($dbRow as $key => $value) {
            if (isset($translations[$key])) {
                $job['additional'][] = array(
                    'key' => $key,
                    'name' => $translations[$key],
                    'value' => $value
                );
            }
        }

        // add columns if the job was a success
        if ($job['status'] == 'success') {
            $descResource = new Data_Model_Resource_Description();
            $descResource->init($job['database']);
            $tableMeta = $descResource->describeTable($job['table']);

            $job['cols'] = $tableMeta['columns'];
        }

        return array('job' => $job, 'status' => 'ok');
    }

    /**
     * Updates a query job.
     * @param int $id id of the query job group
     * @param array $formParams
     * @return array $response
     */
    public function updateJob($id, array $formParams = array()) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->getJobResource()->fetchRow($id);
        if (empty($row) || $row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get query job groups for this user
        $groupsResource = new Query_Model_Resource_Groups();
        $groups = $groupsResource->fetchRows(array(
            'where' => array(
                'user_id = ?' => Daiquiri_Auth::getInstance()->getCurrentId(),
            ),
        ));

        // create the form object
        $form = new Query_Form_Job(array(
            'groups' => $groups,
            'entry' => $row,
            'submit' => 'Update query job'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the group_id needs to be set to NULL
                if ($values['group_id'] === '0') {
                    $values['group_id'] = NULL;
                }

                $this->getResource()->getJobResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
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
        if (empty($row) || $row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_NotFound();
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
        if (empty($row) || $row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_NotFound();
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
        if (empty($row) || $row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_NotFound();
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
     * Creates a query job group.
     * @param int $id id of the query job group
     * @param array $formParams
     * @return array $response
     */
    public function createGroup(array $formParams = array()) {
        // set group resource
        $this->setResource(new Query_Model_Resource_Groups());

        // create the form object
        $form = new Query_Form_Group(array(
            'submit' => 'Update query job group'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // get the current user id
                $values['user_id'] = Daiquiri_Auth::getInstance()->getCurrentId();

                // store the values in the database
                $id = $this->getResource()->insertRow($values);

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a query job group.
     * @param int $id id of the query job group
     * @param array $formParams
     * @return array $response
     */
    public function updateGroup($id, array $formParams = array()) {
        // set group resource
        $this->setResource(new Query_Model_Resource_Groups());

        // get the entry from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($entry['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create the form object
        $form = new Query_Form_Group(array(
            'entry' => $entry,
            'submit' => 'Update query job group'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $this->getResource()->updateRow($id, $values);
                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Moves a query job group whithin the linked list.
     * @param int $id id of the query job group
     * @param array $formParams
     * @return array $response
     */
    public function moveGroup($id, array $formParams = array()) {
        // set group resource
        $this->setResource(new Query_Model_Resource_Groups());

        // get the entry from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($entry['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create the form object
        $form = new Query_Form_MoveGroup(array(
            'prevId' => $entry['prev_id']
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                $errors = array();
                $this->getResource()->moveRow($id, $entry['prev_id'], $entry['next_id'], $values['prev_id'], $errors);

                if (empty($errors)) {
                    return array('status' => 'ok');
                } else {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form, $errors);
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Deletes a query job group.
     * @param int $id id of the query job group
     * @param array $formParams
     * @return array $response
     */
    public function deleteGroup($id, array $formParams = array()) {
        // set group resource
        $this->setResource(new Query_Model_Resource_Groups());

        // get the entry from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($entry['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete query job group');
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
            foreach ($database['tables'] as $key => $table) {
                $database['tables'][$key]['publication_role'] = Daiquiri_Auth::getInstance()->getRole($table['publication_role_id']);
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
                    'value' => $databasesModel->getResource()->quoteIdentifier($database['name']),
                    'tooltip' => $database['description'],
                    'tables' => array()
                );

                foreach ($database['tables'] as $table) {
                    if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($table['publication_role_id'])) {
                        $t = array(
                            'id' => $table['id'],
                            'name' => $table['name'],
                            'value' => $databasesModel->getResource()->quoteIdentifier($database['name'],$table['name']),
                            'tooltip' => $table['description'],
                            'columns' => array(),
                        );

                        foreach ($table['columns'] as $column) {
                            $tooltip = array();
                            if (!empty($column['description'])) $tooltip[] = $column['description'];
                            if (!empty($column['type'])) $tooltip[] = "<i>Type:</i> {$column['type']}";
                            if (!empty($column['unit'])) $tooltip[] = "<i>Unit:</i> {$column['unit']}";
                            if (!empty($column['ucd'])) $tooltip[] = "<i>UCD:</i> {$column['ucd']}";

                            $t['columns'][] = array(
                                'id' => $column['id'],
                                'name' => $column['name'],
                                'value' => $databasesModel->getResource()->quoteIdentifier($column['name']),
                                'tooltip' => implode('<br />',$tooltip)
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
            'value' => $databasesModel->getResource()->quoteIdentifier($userDbName),
            'tooltip' => 'Your personal database',
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
                'value' => $databasesModel->getResource()->quoteIdentifier($userDbName,$usertable),
                'columns' => array()
            );

            try {
                $resource->init($userdb['name'], $usertable);
            } catch (Exception $e) {
                continue;
            }

            $usercolumns = array_keys($resource->fetchCols());
            foreach ($usercolumns as $usercolumn) {
                $table['columns'][] = array(
                    'id' => 'userdb-column-' . $column_id++,
                    'name' => $usercolumn,
                    'value' => $databasesModel->getResource()->quoteIdentifier($usercolumn),
                );
            }

            $userdb['tables'][] = $table;
        }

        $databases[] = $userdb;
        return array('databases' => $databases, 'status' => 'ok');
    }

    /**
     * Returns a set of keywords with description.
     * @return array $response
     */
    public function keywords() {
        $rows = Query_Model_Resource_AbstractProcessor::factory()->getKeywords();
        foreach ($rows as $key => &$row) {
            $row['id'] = $key + 1;
            $row['value'] = $row['name'];
        }
        return array('keywords' => $rows, 'status' => 'ok');
    }

    /**
     * Returns a set of functions with description.
     * @return array $response
     */
    public function nativeFunctions() {
        $rows = Query_Model_Resource_AbstractProcessor::factory()->getFunctions();
        foreach ($rows as $key => &$row) {
            $row['id'] = $key + 1;
            $row['value'] = $row['name'] . '()';
        }
        return array('basic_functions' => $rows, 'status' => 'ok');
    }

    /**
     * Returns all the custom functions which the user has access to.
     * @return array $response
     */
    public function customFunctions() {
        $resource = new Data_Model_Resource_Functions();
        $rows = array();
        foreach ($resource->fetchRows() as $dbRow) {
            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($dbRow['publication_role_id'])) {
                $rows[] = array(
                    'id' => $dbRow['id'],
                    'name' => $dbRow['name'],
                    'value' => $dbRow['name'] . '()',
                    'order' => $dbRow['order'],
                    'tooltip' => $dbRow['description']
                );
            }
        }
        return array('advanced_functions' => $rows, 'status' => 'ok');
    }

    /**
     * Returns all examples which the user has access to.
     * @return array $response
     */
    public function examples() {
        $model = new Query_Model_Examples();
        $rows = array();
        foreach ($model->getResource()->fetchRows(array('order' => 'order ASC')) as $dbRow) {
            if (Daiquiri_Auth::getInstance()->checkPublicationRoleId($dbRow['publication_role_id'])) {
                $rows[] = array(
                    'id' => $dbRow['id'],
                    'name' => $dbRow['name'],
                    'value' => $dbRow['query'],
                    'order' => $dbRow['order']
                );
            }
        }
        return array('examples' => $rows, 'status' => 'ok');
    }

}
