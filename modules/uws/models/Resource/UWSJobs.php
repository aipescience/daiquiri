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
 * Resource class ...
 */
class Uws_Model_Resource_UWSJobs extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets DbTable class.
     */
    public function __construct() {
        $this->addTables(array(
            'Uws_Model_DbTable_UWSJobs',
        ));
    }

    /**
     * Returns a specific row from the (pending) job table
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRow($id) {
        $sqloptions = array();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`jobId` = ?", $id);
        $select->where("`ownerId` = ?", Daiquiri_Auth::getInstance()->getCurrentUsername());

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->current();

        $data = false;

        if($row) {
            $data = $row->toArray();
        }

        return $data;
    }

    /**
     * Returns a specific row from the (pending) job table
     * @param type $id
     * @throws Exception
     * @return type 
     */
    public function fetchRows() {
        $sqloptions = array();

        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);
        $select->where("`ownerId` = ?", Daiquiri_Auth::getInstance()->getCurrentUsername());

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->toArray();
        return $row;
    }

    /**
     * Returns the id of the job with a given UWS job id
     * @param string $UWSJobId
     * @return array
     */
    public function fetchObjectWithId($id) {
        $sqloptions = array();

        // get the primary sql select object
        $select = $this->getTable()->getSelect($sqloptions);

        $select->where("`jobId` = ?", $id);
        $select->where("`ownerId` = ?", Daiquiri_Auth::getInstance()->getCurrentUsername());

        // get the rowset and return
        $row = $this->getTable()->fetchAll($select)->toArray();

        if ($row) {
            //map information to an object
            return $this->_objectFromRow($row[0]);
        }

        return false;
    }

    private function _objectFromRow($row) {
        //create new job object
        $jobUWS = new Uws_Model_Resource_JobSummaryType("job");

        $jobUWS->jobId = $row['jobId'];
        $jobUWS->runId = $row['runId'];
        $jobUWS->ownerId = $row['ownerId'];
        $jobUWS->phase = $row['phase'];
        $jobUWS->quote = $row['quote'];

        if ($row['startTime'] !== "0000-00-00 00:00:00") {
            $datetime = new DateTime($row['startTime']);
            $jobUWS->startTime = $datetime->format('c');
        }

        if ($row['endTime'] !== "0000-00-00 00:00:00") {
            $datetime = new DateTime($row['endTime']);
            $jobUWS->endTime = $datetime->format('c');
        }

        $jobUWS->executionDuration = intval($row['executionDuration']);

        if ($row['destruction'] !== "0000-00-00 00:00:00") {
            $datetime = new DateTime($row['destruction']);
            $jobUWS->destruction = $datetime->format('c');
        }

        //parameters
        $params = Zend_Json::decode($row['parameters']);
        if ($params) {
            foreach ($params as $param) {
                $jobUWS->addParameter($param['id'], $param['value'], $param['byReference'], $param['isPost']);
            }
        }

        //results
        $results = Zend_Json::decode($row['results']);
        if ($results) {
            foreach ($results as $result) {
                $jobUWS->addResult($result['id'], $result['reference']['href']);
            }
        }

        //errorSummary
        $errors = Zend_Json::decode($row['errorSummary']);
        if ($errors) {
            foreach ($errors['messages'] as $error) {
                $jobUWS->addError($error);
            }
        }

        //jobInfo
        $jobUWS->jobInfo = $row['jobInfo'];

        return $jobUWS;
    }

}
