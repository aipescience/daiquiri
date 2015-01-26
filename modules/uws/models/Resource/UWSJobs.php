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

class Uws_Model_Resource_UWSJobs extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets Tablename.
     */
    public function __construct() {
        $this->setTablename('Uws_Jobs');
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
        $select->from('Uws_Jobs');
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
        $select->from('Uws_Jobs');
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
