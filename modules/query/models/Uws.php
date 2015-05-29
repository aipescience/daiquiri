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
    private static $statusQueue = array('queued' => 1, 'running' => 2, 'removed' => 6, 'error' => 4, 'success' => 3, 'timeout' => 5, 'killed' => 5);

    public function __construct() {
        parent::__construct();
    }

    public function getJobList($params) {
        // get jobs
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // get the userid
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        // get rows for this user
        $rows = $this->getResource()->fetchRows(array(
            'where' => array(
                'user_id = ?' => $userId,
                'status_id != ?' => $this->getResource()->getStatusId('removed'),
            ),
            'limit' => 1000,
            'order' => array('time DESC'),
        ));

        $jobs = new Uws_Model_Resource_Jobs();

        foreach ($rows as $job) {
            $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                    "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['id']);
            $status = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$job['status']]];
            $jobs->addJob($job['table'], $href, array($status));
        }

        $resUWSJobs = new Uws_Model_Resource_UWSJobs();

        $pendingJobList = $resUWSJobs->fetchRows();

        foreach ($pendingJobList as $job) {
            $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                    "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['jobId']);
            $status = $job['phase'];
            $jobs->addJob($job['jobId'], $href, array($status));
        }

        return $jobs;
    }

    public function getJob($requestParams) {
        // get the job id
        $id = $requestParams['wild0'];

        // set resource
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());
        $resourceClass = get_class($this->getResource());

        // get the job
        $row = $this->getResource()->fetchRow($id);
        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }
        if ($row['user_id'] !== Daiquiri_Auth::getInstance()->getCurrentId()) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // fetch table statistics
        $stat = $this->getResource()->fetchTableStats($row['database'],$row['table']);
        $job = array_merge($row, $stat);

        // fill UWS object with information
        $jobUWS = new Uws_Model_Resource_JobSummaryType("job");
        $jobUWS->jobId = $job['id'];
        $jobUWS->ownerId = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $jobUWS->phase = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$job['status']]];

        // convert timestamps to ISO 8601
        if ($resourceClass == 'Query_Model_Resource_QQueueQuery') {
            if ($job['timeExecute'] !== "0000-00-00 00:00:00") {
                $datetimeStart = new DateTime($job['timeExecute']);
                $jobUWS->startTime = $datetimeStart->format('c');
            }

            if ($job['timeFinish'] !== "0000-00-00 00:00:00") {
                $datetimeEnd = new DateTime($job['timeFinish']);
                $jobUWS->endTime = $datetimeEnd->format('c');
            }
        } else {
            // for simple queue
            $datetime = new DateTime($job['time']);
            $jobUWS->startTime = $datetime->format('c');
            $jobUWS->endTime = $datetime->format('c');
        }

        // obtain queue information
        $queues = array();
        if ($resourceClass::$hasQueues === true) {
            $queues = $this->getResource()->fetchQueues();

            // find the queue
            foreach ($queues as $queue) {
                if ($queue['name'] === $job['queue']) {
                    $jobUWS->executionDuration = $queue['timeout'];
                    break;
                }
            }
        } else {
            // no queue information - execution infinite
            $jobUWS->executionDuration = 0;
        }

        // no destruction time supported, so return hillariously high number
        $datetime = new DateTime('31 Dec 2999');
        $jobUWS->destruction = $datetime->format('c');

        // fill the parameter part of the UWS with the original information stored in the queue
        foreach ($job as $key => $value) {
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
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . '/query/download/stream/table/' .urlencode($job['table']) . '/format/' . $adapter['format'];

                $jobUWS->addResult($id, $href);
            }
        } else if ($jobUWS->phase === "ERROR") {
            $jobUWS->addError($job['error']);
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
        $resource = Query_Model_Resource_AbstractQuery::factory();
        $queues = array();
        if ($resource::$hasQueues === true && isset($job->parameters['queue'])) {
            $queues = $resource->fetchQueues();

            // find the queue
            foreach ($queues as $queue) {
                if ($queue['name'] === $job->parameters['queue']->value) {
                    $job->executionDuration = $queue['timeout'];
                    break;
                }
            }
        } else if ($resource::$hasQueues === true) {
            // no queue has been specified, but we support queues - if executionDuration is 0, use default queue
            // otherwise find the desired queue
            $queues = $resource->fetchQueues();

            if ($job->executionDuration === 0) {
                // use default queue here
                $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;

                foreach ($queues as $currQueue) {
                    if ($currQueue['name'] === $queue) {
                        $job->executionDuration = $currQueue['timeout'];
                        $job->addParameter("queue", $currQueue['name']);
                        break;
                    }
                }
            } else {
                // find a queue that matches the request (i.e. is nearest to the request)
                $maxQueueTimeout = 0;
                $maxQueue = false;
                $deltaQueue = 9999999999999999999999999999999999;
                $queue = false;
                foreach ($queues as $currQueue) {
                    if ($currQueue['timeout'] > $maxQueue) {
                        $maxQueueTimeout = $currQueue['timeout'];
                        $maxQueue = $currQueue;
                    }

                    if ($currQueue['timeout'] >= $job->executionDuration) {
                        $currDelta = $currQueue['timeout'] - $job->executionDuration;
                        if ($currDelta < $deltaQueue) {
                            $queue = $currQueue;
                            $deltaQueue = $currDelta;
                        }
                    }
                }

                if ($queue === false) {
                    $queue = $maxQueue;
                }

                $job->addParameter("queue", $currQueue['name']);
                $job->executionDuration = $currQueue['timeout'];
            }
        }

        // now check if everything is there that we need...
        $tablename = null;
        $sql = null;
        $queue = null;
        $errors = array();

        if (!isset($job->parameters['query']) || ($resource::$hasQueues === true && !isset($job->parameters['queue']))) {
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

        if ($resource::$hasQueues === true) {
            $queue = $job->parameters['queue']->value;
        }

        // submit job
        // validate query
        $job->resetErrors();
        $model = new Query_Model_Query();
        try {
            if ($model->validate($sql, false, $tablename, $errors) !== true) {
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
        if ($resource::$hasQueues === true) {
            $response = $model->query($sql, false, $tablename, array("queue" => $queue, "jobId" => $job->jobId));
        } else {
            $response = $model->query($sql, false, $tablename, array("jobId" => $job->jobId));
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

}        
