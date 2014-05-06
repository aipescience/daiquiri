<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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

    //status = array('PENDING', 'QUEUED', 'EXECUTING', 'COMPLETED', 'ERROR', 'ABORTED', 'UNKNOWN', 'HELD', 'SUSPENDED');
    private static $statusQueue = array('pending' => 1, 'running' => 2, 'removed' => 6, 'error' => 4, 'success' => 3, 'timeout' => 5, 'killed' => 5);

    public function __construct() {
        parent::__construct();
    }

    public function getJobList($params) {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        $jobList = $model->index();

        $jobs = new Uws_Model_Resource_Jobs();

        foreach ($jobList as $job) {
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

    public function getJob($params) {
        //obtain job information
        $job = $this->_getJob($params['wild0']);

        if ($job === false) {
            return false;
        }

        //fill UWS object with information
        $jobUWS = new Uws_Model_Resource_JobSummaryType("job");

        $jobUWS->jobId = $job['id']['value'];
        $jobUWS->ownerId = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $jobUWS->phase = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$job['status']['value']]];

        if (isset($job['time'])) {
            //for simple queue
            //convert to ISO 8601
            $datetime = new DateTime($job['time']['value']);
            $jobUWS->startTime = $datetime->format('c');
            $jobUWS->endTime = $datetime->format('c');
        } else {
            //for paqu queue
            //convert to ISO 8601
            if ($job['timeExecute']['value'] !== "0000-00-00 00:00:00") {
                $datetimeStart = new DateTime($job['timeExecute']['value']);
                $jobUWS->startTime = $datetimeStart->format('c');
            }

            if ($job['timeFinish']['value'] !== "0000-00-00 00:00:00") {
                $datetimeEnd = new DateTime($job['timeFinish']['value']);
                $jobUWS->endTime = $datetimeEnd->format('c');
            }
        }

        // obtain queue information
        $resource = Query_Model_Resource_AbstractQuery::factory();
        $queues = array();
        if ($resource::$hasQueues === true) {
            $queues = $resource->fetchQueues();

            //find the queue
            foreach ($queues as $queue) {
                if ($queue['name'] === $job['queue']['value']) {
                    $jobUWS->executionDuration = $queue['timeout'];
                    break;
                }
            }
        } else {
            //no queue information - execution infinite
            $jobUWS->executionDuration = 0;
        }

        //no destruction time supported, so return hillariously high number
        $datetime = new DateTime('31 Dec 2999');
        $jobUWS->destruction = $datetime->format('c');

        //fill the parameter part of the UWS with the original information stored in the queue
        foreach ($job as $key => $param) {
            //allowed parameters
            switch ($key) {
                case 'database':
                case 'table':
                case 'query':
                case 'actualQuery':
                case 'queue':
                    $jobUWS->addParameter($key, $param['value']);
                    break;
                default:
                    break;
            }
        }

        //add link to results if needed
        if ($jobUWS->phase === "COMPLETED") {
            //we have results!
            $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/query/index/stream/table/" .
                    urlencode($job['table']['value']) . "/format/csv";
            $jobUWS->addResult($job['table']['value'], $href);
        } else if ($jobUWS->phase === "ERROR") {
            $jobUWS->addError($job['error']['value']);
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
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        try {
            $model->remove($job->jobId, array("id" => $job->jobId));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function abortJobImpl(Uws_Model_Resource_JobSummaryType &$job) {
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        try {
            $model->kill($job->jobId, array("id" => $job->jobId));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function runJob(Uws_Model_Resource_JobSummaryType &$job) {
        //obtain queue information
        $resource = Query_Model_Resource_AbstractQuery::factory();
        $queues = array();
        if ($resource::$hasQueues === true && isset($job->parameters['queue'])) {
            $queues = $resource->fetchQueues();

            //find the queue
            foreach ($queues as $queue) {
                if ($queue['name'] === $job->parameters['queue']->value) {
                    $job->executionDuration = $queue['timeout'];
                    break;
                }
            }
        } else if ($resource::$hasQueues === true) {
            //no queue has been specified, but we support queues - if executionDuration is 0, use default queue
            //otherwise find the desired queue
            $queues = $resource->fetchQueues();

            if ($job->executionDuration === 0) {
                //use default queue here
                $queue = Daiquiri_Config::getInstance()->query->query->qqueue->defaultQueue;

                foreach ($queues as $currQueue) {
                    if ($currQueue['name'] === $queue) {
                        $job->executionDuration = $currQueue['timeout'];
                        $job->addParameter("queue", $currQueue['name']);
                        break;
                    }
                }
            } else {
                //find a queue that matches the request (i.e. is nearest to the request)
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

        //now check if everything is there that we need...
        $tablename = null;
        $sql = null;
        $queue = null;
        $errors = array();

        if (!isset($job->parameters['query']) || ($resource::$hasQueues === true && !isset($job->parameters['queue']))) {
            //throw error
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

        //submit job
        //validate query
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
            //throw error
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
            //throw error
            $job->addError($response['errors']);
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));
            return;
        }

        //clean up stuff (basically just remove the job in the temorary UWS job store - if we are here
        //everything has been handeled by the queue)
        $resource = new Uws_Model_Resource_UWSJobs();
        $resource->deleteRow($job->jobId);
    }

    private function _getJob($jobId) {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        try {
            $job = $model->show($jobId);
        } catch (Exception $e) {
            return false;
        }

        return $job;
    }

}