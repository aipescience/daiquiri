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

class Uws_Model_Resource_UWSJobs extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets Tablename.
     */
    public function __construct() {
        $this->addTablename('Uws_UWSJobs');
    }

    /**
     * Fetches a specific row from the (pending) job table
     * @param string $id UWSJobId of the job
     * @throws Exception
     * @return array $row
     */
    public function fetchRow($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $select = $this->select();
        $select->from('Uws_UWSJobs');
        $select->where("jobId = ?", $id);
        $select->where("ownerId = ?", Daiquiri_Auth::getInstance()->getCurrentUsername());

        return $this->fetchOne($select);
    }

    /**
     * Fetches a set of rows from the (pending) job table
     * @throws Exception
     * @return array $rows
     */
    public function fetchRows(array $sqloptions = array()) {
        $select = $this->select();
        $select->from('Uws_UWSJobs');
        $select->where("ownerId = ?", Daiquiri_Auth::getInstance()->getCurrentUsername());

        return $this->fetchAll($select);
    }

    /**
     * Returns the information of the job with a given UWS job id
     * @param string $id UWSJobId of the job
     * @return array
     */
    public function fetchObjectWithId($id) {
        if (empty($id)) {
            throw new Exception('$id not provided in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        }

        $row = $this->fetchRow($id);
        if ($row) {
            // map information to an object
            return $this->_objectFromRow($row);
        } else {
            return false;
        }
    }

    /**
     * Maps the information of a row in the UwsJobs table to an object
     * @param array $row database row
     * @return array$jobUWS
     */
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
