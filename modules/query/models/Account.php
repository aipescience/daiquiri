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

        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get the sqloptions needed to show the list of jobs
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // get rows and return
        $rows = array();
        try {
            $dbRows = $this->getResource()->fetchRows(array(
                'where' => array(
                    'user_id = ?' => $userId,
                    'status_id != ?' => $this->getResource()->getStatusId('removed'),
                ),
                'order' => array($this->getResource()->getTimeField() . ' DESC'),
                'limit' => 1000
            ));
        } catch (Exception $e) {
            $dbRows = array();
        }

        foreach ($dbRows as $dbRow) {
            $row = array();
            foreach (array('id', 'table', 'status') as $col) {
                $row[$col] = $dbRow[$col];
            }
            $row['time'] = $dbRow[$this->getResource()->getTimeField()];
            $rows[] = $row;
        }

        // get number of currently active jobs
        $resourceClass = get_class($this->getResource());
        if ($resourceClass::$hasQueues) {
            $nactive = $this->getResource()->fetchNActive();
        } else {
            $nactive = false;
        }

        // check if guest or not
        $guest = (Daiquiri_Auth::getInstance()->getCurrentRole() === 'guest');

        // get the quota information
        $usrGrp = Daiquiri_Auth::getInstance()->getCurrentRole();
        if ($usrGrp !== null) {
            $quota = array();

            // get database stats
            $stats = $this->getResource()->fetchDatabaseStats();

            // space in byte
            $usedSpace = (float) $stats['db_size'];

            // get the quota space
            $quota['max'] = Daiquiri_Config::getInstance()->query->quota->$usrGrp;

            // parse the quota to resolve KB, MB, GB, TB, PB, EB...
            preg_match("/([0-9.]+)\s*([KMGTPEBkmgtpeb]*)/", $quota['max'], $parse);
            $quotaSpace = (float) $parse[1];
            $unit = $parse[2];

            switch (strtoupper($unit)) {
                case 'EB':
                    $quotaSpace *= 1024;
                case 'PB':
                    $quotaSpace *= 1024;
                case 'TB':
                    $quotaSpace *= 1024;
                case 'GB':
                    $quotaSpace *= 1024;
                case 'MB':
                    $quotaSpace *= 1024;
                case 'KB':
                    $quotaSpace *= 1024;
                default:
                    break;
            }

            if ($usedSpace > $quotaSpace) {
                $quota['exceeded'] = true;
            } else {
                $quota['exceeded'] = false;
            }

            $unit = ' byte';
            foreach (array('KB','MB','GB','TB','PB','EB') as $u) {
                if ($usedSpace > 1024) {
                    $usedSpace /= 1024.0;
                    $unit = $u;
                }
            }

            $quota['used'] = ((string) floor($usedSpace * 100) / 100 ) . ' ' . $unit;

        } else {
            $quota = false;
        }

        return array(
            'status' => 'ok',
            'jobs' => $rows,
            'database' => array(
                'message' => $message,
                'nactive' => $nactive,
                'guest' => $guest,
                'quota' => $quota,
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
            'time' => $dbRow[$this->getResource()->getTimeField()],
            'additional' => array()
        );

        foreach(array('id','database','table','status','error','username','timeQueue','timeQuery') as $key) {
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

        // get actial query if there is one
        if (isset($dbRow['actualQuery'])) {
            $job['actualQuery'] = str_replace("; ",";\n",$dbRow['actualQuery']);
            unset($dbRow['actualQuery']);
        }

        // fetch table statistics
        if ($job['status'] == 'success') {
            $stat = $this->getResource()->fetchTableStats($job['database'],$job['table']);
        } else {
            $stat = array();
        }

        // ret row count
        if (isset($stat['tbl_row'])) {
            $job['nrows'] = $stat['tbl_row'];
            unset($stat['tbl_row']);
        }

        // create additional array
        $translations = $this->getResource()->getTranslations();
        foreach (array_merge($dbRow, $stat) as $key => $value) {
            $job['additional'][] = array(
                'key' => $key,
                'name' => $translations[$key],
                'value' => $value
            );
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
                            $tooltip = '';
                            if (!empty($column['description'])) $tooltip .= "<p>{$column['description']}</p>";
                            if (!empty($column['type'])) $tooltip .= "<p><i>Type:</i> {$column['type']}</p>";
                            if (!empty($column['unit'])) $tooltip .= "<p><i>Unit:</i> {$column['unit']}</p>";
                            if (!empty($column['ucd'])) $tooltip .= "<p><i>UCD:</i> {$column['ucd']}</p>";

                            $t['columns'][] = array(
                                'id' => $column['id'],
                                'name' => $column['name'],
                                'value' => $databasesModel->getResource()->quoteIdentifier($column['name']),
                                'tooltip' => $tooltip
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
        return array('native_functions' => $rows, 'status' => 'ok');
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
                    'value' => $dbRow['query'],
                    'order' => $dbRow['order'],
                    'tooltip' => $dbRow['description']
                );
            }
        }
        return array('custom_functions' => $rows, 'status' => 'ok');
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
