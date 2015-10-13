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

class Query_Model_Uws extends Uws_Model_UwsAbstract {

    // status = array('PENDING', 'QUEUED', 'EXECUTING', 'COMPLETED', 'ERROR', 'ABORTED', 'UNKNOWN', 'HELD', 'SUSPENDED');
    private static $statusQueue = array(
        'queued' => 1,
        'running' => 2,
        'removed' => 6,
        'error' => 4,
        'success' => 3,
        'timeout' => 5,
        'killed' => 5
    );

    public function __construct() {
        parent::__construct();
    }

    public function getJobList($params) {
        // get jobs
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // Filter job list, introduced with UWS version 1.1
        // parse here and now for LAST parameter and status (= phase)!

        // map uws phase names to internal status names:
        $status_uws = array(
            'QUEUED' => 'queued',
            'EXECUTING' => 'running',
            'ARCHIVED' => 'removed',
            'ERROR' => 'error',
            'COMPLETED' => 'success',
            'ABORTED' => 'killed' // or 'timeout'
        );
        // NOTE: held, suspended and unknown-filter should return nothing for daiquiri,
        // for DirectQuery, only 'success', 'error' and 'removed' exist + pending

        $statuslist = array();
        $phase = '';
        if (array_key_exists('PHASE', $params)) {
            // NOTE: UWS1.1 allows to give more than one PHASE!
            /* foreach ($phases as $phase) {
                if (array_key_exists($phase, $uws_status)) {
                    $statuslist[] = $uws_status[$phase];
                }
            }

            $statusfilter = ' AND (';
            foreach ($statuslist as $status_id) {
                $statusfilter .=  ' status_id = ' . $status_id . ' OR';
            }
            $statusfilter = substr($statusfilter, 0, -2) . ')';
            */

            // just use the last PHASE for the moment
            $phase = $params['PHASE'];
            if (array_key_exists($phase, $status_uws)) {
                $statuslist[] = $status_uws[$phase];
                $statusf = array('status_id = ?' => $this->getResource()->getStatusId($status_uws[$phase]));
            }


        }
        if (empty($statuslist) && ($phase != "PENDING")) {
            //$statusfilter = ' AND status_id != ' . $this->getResource()->getStatusId('removed');
            $statusf = array('status_id != ?' => $this->getResource()->getStatusId('removed'));
        }

        $limit = 1000; // max. limit of returned rows
        if (array_key_exists('LAST', $params)) {
            $limit  = $params['LAST'];
            // check if string contains only digits (positive integer!),
            // if so, convert to integer
            if (isset($limit) && ctype_digit($limit)) {
                $limit = intval($limit);
            }
        }

        // get the userid
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        $jobs = new Uws_Model_Resource_Jobs();

        // If PENDING jobs are requested, do not need to query this db table.
        // Pending jobs are stored only in the db table queried below.
        if ($phase != "PENDING") {
            // cannot use statusfilter yet, because need to tweak sql-statement below to allow more than one phase-condition,
            // thus use status for now.
            $wherelist = array('user_id = ?' => $userId);
            $wherelist = array_merge($wherelist, $statusf);

            // get rows for this user
            $rows = $this->getResource()->fetchRows(array(
                'where' => $wherelist,
                'limit' => $limit,
                'order' => array('time DESC'),
            ));


            foreach ($rows as $job) {
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['id']);
                $status = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$job['status']]];
                $jobs->addJob($job['table'], $href, array($status));
            }

            // If LAST parameter had been used, then ordering requested by standard is by *ascending* startTimes:
            if (array_key_exists('LAST', $params)) {
                $jobs->jobref = array_reverse($jobs->jobref);
            }

        }

        // add pending/error jobs, but only if no PHASE-filter was given or phase-filter was PENDING, ERROR or ABORTED
        // NOTE: This list also contains jobs in following phases: PENDING, ERROR, ABORTED
        // NOTE: Not sure about SUSPENDED, HELD or UNKNOWN
        $jobs2 = new Uws_Model_Resource_Jobs();

        if (empty($phase) || $phase == 'ERROR' || $phase == 'PENDING' || $phase == 'ABORTED') {
            $resUWSJobs = new Uws_Model_Resource_UWSJobs();

            $pendingJobList = $resUWSJobs->fetchRows(); //where is the check for the userId?? --> inside UWSJobs

            foreach ($pendingJobList as $job) {
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['jobId']);
                $status = $job['phase'];
                if (empty($phase) || $phase === $status) {
                    $jobs2->addJob($job['jobId'], $href, array($status));
                }
            }
        }

        // Now append pending jobs or sort the jobs by their time
        // and apply a final cut to given $limit if it was required.
        // (Could also do this sorting even if LAST was not given ...)
        if (array_key_exists('LAST', $params)) {
            $jobs2->jobref = array_reverse($jobs2->jobref);

            // Either just append pending and error jobs
            // (which may have NULL startTimes) just at the end ...
            $jobs->jobref = array_merge($jobs->jobref, $jobs2->jobref);

            // Or assume that creation time is correlated to the jobIds
            // (the ones appended at href), so sort by href.
            // This may not be the best approach, but it works with the current
            // setup.
            $sortcolumn = array();
            foreach ($jobs->jobref as $key => $row) {
                $sortcolumn[$key] = $row->reference->href;
            }
            array_multisort($sortcolumn, SORT_ASC, $jobs->jobref);
            // Cut, only keep number of jobs required by LAST
            $jobs->jobref = array_slice($jobs->jobref, -$limit, $limit);

        }

        return $jobs;
    }

    public function getJob($requestParams) {
        // get the job id
        $id = $requestParams['wild0'];

        // set resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get the job
        $row = $this->getResource()->fetchRow($id);
        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // fill UWS object with information
        $jobUWS = new Uws_Model_Resource_JobSummaryType("job");
        $jobUWS->jobId = $row['id'];
        $jobUWS->ownerId = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $jobUWS->phase = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$row['status']]];

        // convert timestamps to ISO 8601
        if (get_class($this->getResource()) == 'Query_Model_Resource_QQueueQuery') {
            if ($row['timeExecute'] !== "0000-00-00 00:00:00") {
                $datetimeStart = new DateTime($row['timeExecute']);
                $jobUWS->startTime = $datetimeStart->format('c');
            }

            if ($row['timeFinish'] !== "0000-00-00 00:00:00") {
                $datetimeEnd = new DateTime($row['timeFinish']);
                $jobUWS->endTime = $datetimeEnd->format('c');
            }
        } else {
            // for simple queue
            $datetime = new DateTime($row['time']);
            $jobUWS->startTime = $datetime->format('c');
            $jobUWS->endTime = $datetime->format('c');
        }

        if ($this->getResource()->hasQueues()) {
            $config = $this->getResource()->fetchConfig();
            $jobUWS->executionDuration = $config["userQueues"][$row['queue']]['timeout'];
        } else {
            // no queue information - execution infinite
            $jobUWS->executionDuration = 0;
        }

        // no destruction time supported, so return hillariously high number
        $datetime = new DateTime('31 Dec 2999');
        $jobUWS->destruction = $datetime->format('c');

        // fill the parameter part of the UWS with the original information stored in the queue
        foreach ($row as $key => $value) {
            // allowed parameters
            switch ($key) {
                case 'database':
                case 'table':
                case 'query':
                case 'actualQuery':
                case 'queue':
                    $jobUWS->addParameter($key, $value);
                    break;
                default:
                    break;
            }
        }

        // add link to results if needed
        if ($jobUWS->phase === "COMPLETED") {
            foreach (Daiquiri_Config::getInstance()->getQueryDownloadAdapter() as $adapter) {

                $id = $adapter['suffix'];
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . '/query/download/stream/table/' .urlencode($row['table']) . '/format/' . $adapter['format'];

                $jobUWS->addResult($id, $href);
            }
        } else if ($jobUWS->phase === "ERROR") {
            $jobUWS->addError($row['error']);
        }

        return $jobUWS;
    }

    public function getError(Uws_Model_Resource_JobSummaryType $job) {
        if (empty($job->errorSummary->messages)) {
            return "";
        } else {
            return implode("\n", $job->errorSummary->messages);
        }
    }

    public function getQuote() {
        return NULL;
    }

    public function setDestructTimeImpl(Uws_Model_Resource_JobSummaryType &$job, $newDestructTime) {
        //no destruction time supported, so return hillariously high number
        $datetime = new DateTime('31 Dec 2999');
        $jobUWS->destruction = $datetime->format('c');

        //this model does not support destruction time, so we don't need to store anything and just return
        return $jobUWS->destruction;
    }

    public function setExecutionDurationImpl(Uws_Model_Resource_JobSummaryType &$job, $newExecutionDuration) {
        //no dynamic execution duration update supported, so don't change anything and return the already
        //saved value

        return $jobUWS->executionDuration;
    }

    public function deleteJobImpl(Uws_Model_Resource_JobSummaryType &$job) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->fetchRow($job->jobId);
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // remove job
        $this->getResource()->removeJob($job->jobId);
        return true;
    }

    public function abortJobImpl(Uws_Model_Resource_JobSummaryType &$job) {
        // set job resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get job and check permissions
        $row = $this->getResource()->fetchRow($job->jobId);
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // kill job
        $this->getResource()->killJob($id);
        return true;
    }

    public function runJob(Uws_Model_Resource_JobSummaryType &$job) {
        // obtain queue information
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());
        $config = $this->getResource()->fetchConfig();

        if ($this->getResource()->hasQueues()) {

            if (isset($job->parameters['queue'])) {
                $jobUWS->executionDuration = $config["userQueues"][$job->parameters['queue']->value]['timeout'];
            } else {
                // no queue has been specified, but we support queues - if executionDuration is 0, use default queue
                // otherwise find the desired queue
                if ($job->executionDuration === 0) {
                    // use default queue here
                    $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;
                } else {
                    // find a queue that matches the request (i.e. is nearest to the request)
                    $queue = $this->_findQueue($job->executionDuration,$config["userQueues"]);
                }

                $jobUWS->executionDuration = $config["userQueues"][$queue]['timeout'];
                $job->addParameter("queue", $queue);
            }
        }

        // now check if everything is there that we need...
        $tablename = null;
        $sql = null;
        $queue = null;
        $errors = array();

        if (!isset($job->parameters['query']) || ($this->getResource()->hasQueues() && !isset($job->parameters['queue']))) {
            // throw error
            $job->addError("Incomplete job");
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));
            return;
        }

        if (isset($job->parameters['table'])) {
            $tablename = $job->parameters['table']->value;
        }

        $sql = $job->parameters['query']->value;

        if ($this->getResource()->hasQueues()) {
            $queue = $job->parameters['queue']->value;
        }

        // submit job

        // prepare sources array
        $sources = array();

        // validate query
        $job->resetErrors();
        $model = new Query_Model_Query();
        try {
            if ($model->validate($sql, false, $tablename, $errors, $sources) !== true) {
                //throw error
                foreach ($errors as $error) {
                    $job->addError($error);
                }

                $resource = new Uws_Model_Resource_UWSJobs();
                $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));
                return;
            }
        } catch (Exception $e) {
            // throw error
            $job->addError($e->getMessage());
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));
            return;
        }

        // submit query
        if ($this->getResource()->hasQueues()) {
            $response = $model->query($sql, false, $tablename, $sources, array("queue" => $queue, "jobId" => $job->jobId),'uws');
        } else {
            $response = $model->query($sql, false, $tablename, $sources, array("jobId" => $job->jobId),'uws');
        }

        if ($response['status'] !== 'ok') {
            // throw error
            foreach ($response['errors'] as $error) {
                $job->addError($error);
            }
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));
            return;
        }

        // clean up stuff (basically just remove the job in the temorary UWS job store - if we are here
        // everything has been handeled by the queue)
        $resource = new Uws_Model_Resource_UWSJobs();
        $resource->deleteRow($job->jobId);
    }

    /**
     * Finds a queue that matches the requested execution time (i.e. is nearest to it)
     * @param  int    $executionDuration requested execution time
     * @param  array  $queues            user queues from config
     * @return string $queue
     */
    private function _findQueue($executionDuration,$queues) {
        $maxQueueTimeout = 0;
        $maxQueueName = false;

        $deltaQueue = PHP_INT_MAX;
        $queue = false;

        foreach ($queues as $currQueueName => $currQueue) {
            if ($currQueue['timeout'] > $maxQueueTimeout) {
                $maxQueueTimeout = $currQueue['timeout'];
                $maxQueueName = $currQueueName;
            }

            if ($currQueue['timeout'] >= $executionDuration) {
                $currDelta = $currQueue['timeout'] - $executionDuration;
                if ($currDelta < $deltaQueue) {
                    $queue = $currQueueName;
                    $deltaQueue = $currDelta;
                }
            }
        }

        if ($queue === false) {
            $queue = $maxQueueName;
        }

        return $queue;
    }
}
