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

    // status = array('PENDING', 'QUEUED', 'EXECUTING', 'COMPLETED', 'ERROR', 'ABORTED', 'UNKNOWN', 'HELD', 'SUSPENDED', 'ARCHIVED');
    // map between internal status names for different queues and
    // official UWS phase names as stored in Uws_Model_UwsAbstract
    private static $statusQueue = array(
        'queued' => 1,
        'running' => 2,
        'removed' => 9,
        'error' => 4,
        'success' => 3,
        'timeout' => 5,
        'killed' => 5
    );

    public function __construct() {
        parent::__construct();
    }

    private function getQueryStatusNames($uwsphase) {
        // get the internal query status name defined by $statusQueue for
        // a given official UWS phase name as defined in Uws_Model_UwsAbstract;
        // also take disambiguity with ABORTED into account,
        // return array of matching status names
        $uwsphase_id = array_search($uwsphase, Uws_Model_UwsAbstract::$status);
        $status_names = array();
        if ($uwsphase_id) {
            // find all matching internal names
            $status_names = array_keys(self::$statusQueue, $uwsphase_id);
        }
        return $status_names;
    }

    public function getJobList($params) {
        // get jobs
        $this->setResource(Query_Model_Resource_AbstractQuery::factory());

        // Filter job list, introduced with UWS version 1.1
        // parse here and now for LAST/AFTER and PHASE filter parameters

        // Request query string may contain repeated PHASE key,
        // which is not recognized as PHASE-array by standard PHP/Zend-functions,
        // thus parse manually:
        $queryparams = Daiquiri_Config::getInstance()->getMultiQuery();

        $statuslist = array();
        $wherestatus = array();
        $phases = array();
        if (array_key_exists('PHASE', $queryparams)) {
            $phases = $queryparams['PHASE'];

            // collect internal status_ids for requested uws-phases
            if (!is_array($phases)) {
                $phases = array($phases);
            }
            foreach ($phases as $phase) {
                if ( !in_array($phase, Uws_Model_UwsAbstract::$status) ) {
                    throw new Daiquiri_Exception_BadRequest("Supplied PHASE value '".$phase."' is not a valid UWS phase.");
                }
                $status_names = $this->getQueryStatusNames($phase);
                foreach ($status_names as $status_name) {
                    if ($status_id = $this->getResource()->getStatusId($status_name)) {
                        $statuslist[] = $status_id;
                    }
                }
            }
        }

        // construct where condition based on PHASE-filters
        if (!empty($statuslist)) {
            $wherestatus = '';
            foreach ($statuslist as $status_id) {
                $wherestatus .= ' status_id = ' . $status_id . ' OR';
            }
            // remove trailing OR
            $wherestatus = array(substr($wherestatus, 0, -2));
        } else if ($phases) {
            // there are valid UWS phases supplied as query parameters
            // (otherwise there would have been an error beforehand), but
            // they are not in the statuslist, so the resource (queue)
            // does not support them (or it's the PENDING jobs)
            // => no need to return anything from this job list
            $wherestatus = NULL;
        } else {
            // statuslist is empty and no phase-filter was given
            // => shall return everything except removed jobs (ARCHIVED phase)
            $wherestatus = array('status_id != ?' => $this->getResource()->getStatusId('removed'));
        }

        $limit = 1000; // default limit of returned rows, NULL = no limit
        // should actually redirect to {url}?LAST=10000 or so, if there is a limit!

        // check LAST keyword and set $limit accordingly
        if (array_key_exists('LAST', $params)) {
            $last = $params['LAST'];
            // check if string contains only digits (-> positive integers) and not 0
            // if so, convert to integer
            if (isset($last) && ctype_digit($last) && $last > 0) {
                $last = intval($last);
                // set limit (maybe restrict to max. limit here?)
                $limit = $last;
            } else {
                throw new Daiquiri_Exception_BadRequest("Supplied LAST value '".$last."' is not a positive integer.");
            }
        }

        $whereafter = array();
        if (array_key_exists('AFTER', $params)) {
            $after = $params['AFTER'];
            $after = strtotime($after);
            if (!$after) {
                throw new Daiquiri_Exception_BadRequest("Cannot parse the timestamp given with AFTER keyword (".$params['AFTER']."), need a UTC-timestamp in ISO 8601 format without timezone-information.");
            }
            // provided time is UTC, must convert system time on DB correctly to UTC before comparing,
            // CONVERT_TZ also takes day saving time correctly into account
            $whereafter = array("CONVERT_TZ(time, 'SYSTEM', '+0:00') > ?" => date('Y-m-d H:i:s', $after));
        }

        // get the userid
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();

        $joblist = array();
        // If only PENDING jobs are requested, do not need to query this db table,
        // pending jobs are stored only in the db table queried below.
        if ( ! (count($phases) == 1 && $phases[0] == "PENDING") && !is_null($wherestatus) ) {
            $whereuser = array('user_id = ?' => $userId);
            $wherelist = array_merge($whereuser, $wherestatus, $whereafter);

            // get rows for this user
            $rows = $this->getResource()->fetchRows(array(
                'where' => $wherelist,
                'order' => array('time DESC'),
                'limit' => $limit,
            ));

            foreach ($rows as $job) {
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['id']);
                $status = Query_Model_Uws::$status[Query_Model_Uws::$statusQueue[$job['status']]];
                $jobId = $job['id'];

                $joblist[] = array('id' => $jobId,
                                   'href' => $href,
                                   'status' => $status,
                                   'time' => $job['time'],
                                   'creationTime' => $job['time'],
                                   'runId' => $job['table'],
                                   'ownerId' => Daiquiri_Auth::getInstance()->getCurrentUsername()
                                   );
            }
        }

        // add pending/error jobs, but only if no PHASE-filter was given
        // or phase-filter was PENDING, ERROR or ABORTED
        // NOTE: Not sure about SUSPENDED, HELD or UNKNOWN
        $pendingJoblist = array();
        if ( empty($phases) || in_array("PENDING", $phases) || in_array("ERROR", $phases) || in_array("ABORTED", $phases) ) {
            $resUWSJobs = new Uws_Model_Resource_UWSJobs();

            // add AFTER condition and/or ignore startTime=NULL in case of AFTER/LAST
            $whereafter = array();
            if (isset($after)) {
                $whereafter = array("CONVERT_TZ(creationTime, 'SYSTEM', '+0:00') > ?" => date('Y-m-d H:i:s', $after));
            }
            else if (isset($last)) {
                $whereafter = array('creationTime IS NOT NULL'); // should never happen actually ... (maybe older jobs with no creation time yet ...)
            }

            $pendingRows = $resUWSJobs->fetchRows(array(
                'where' => $whereafter,
                'order' => array('creationTime DESC'),
                'order' => array('jobId DESC'), // add this here for useful ordering if creationTime=NULL (PENDING jobs)
                'limit' => $limit,
            )); // the check for the userId is inside UWSJobs

            foreach ($pendingRows as $job) {
                $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($params['moduleName']) . "/" . urlencode($job['jobId']);
                $status = $job['phase'];
                $jobId = $job['jobId'];

                if ( empty($phase) || in_array($status, $phases) ) {
                    $pendingJoblist[] = array('id' => $jobId,
                                              'href' => $href,
                                              'status' => $status,
                                              'time' => $job['startTime'],
                                              'creationTime' => $job['creationTime'],
                                              'runId' => $job['runId'],
                                              'ownerId' => $job['ownerId']
                                             );
                }
            }
        }

        // reverse job sort order, since standard requires for LAST/AFTER keywords
        // *ascending* startTimes (but needed initial DESC order in queries for most recent limit)
        // -- reverse ordering not needed anymore! Yeah! (Feb 2016)

        // merge both lists ...
        $joblist = array_merge($joblist, $pendingJoblist);

        // and sort by creationTime (< 10 ms for 10,000 jobs, so it's fast enough to do it always)
        $sortcolumn = array();
        foreach ($joblist as $job) {
            $sortcolumn[] = $job['creationTime'];
        }
        array_multisort($sortcolumn, SORT_DESC, $joblist);

        // apply final cut, if required
        if (array_key_exists('LAST', $params)) {
            // cut the list, only keep number of jobs required by LAST or given by limit
            if (!is_null($limit)) {
                $joblist = array_slice($joblist, 0, $limit);
            }
        }

        // copy jobs into jobs-object
        $jobs = new Uws_Model_Resource_Jobs();
        foreach ($joblist as $job) {
            $jobs->addJob($job['id'], $job['href'], array($job['status']), $job['creationTime'], $job['runId'], $job['ownerId']);
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
        $jobUWS->runId = $row['table']; // our interpretation of runId: use it as the job name = table name

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

            if ($row['time'] != "0000-00-00 00:00:00") {
                $datetimeCreation = new DateTime($row['time']);
                $jobUWS->creationTime = $datetimeCreation->format('c');
            }

        } else {
            // for simple queue
            $datetime = new DateTime($row['time']);
            $jobUWS->startTime = $datetime->format('c');
            $jobUWS->endTime = $datetime->format('c');
            $jobUWS->creationTime = $datetime->format('c');
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

        // job is put into final state; could set endTime here, if not existing yet

        // kill job
        $this->getResource()->killJob($job->jobId);
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

            // job is in final state now, add startTime and endTime
            $now = date('Y-m-d\TH:i:s');
            $resource->updateRow($job->jobId, array("startTime" => $now, "endTime" => $now));

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
        // We will set a startTime here only if the validation results in an
        // error; otherwise the startTime will be set once execution of the
        // job starts. This is the expected behaviour as discussed
        // on the IVOA mailing list (fall 2015).
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

                // job is in final state now (ERROR), add startTime and endTime
                $now = date('Y-m-d\TH:i:s');
                $resource->updateRow($job->jobId, array("startTime" => $now, "endTime" => $now));

                return;
            }
        } catch (Exception $e) {
            // throw error
            $job->addError($e->getMessage());
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));

            // job is in final state now (ERROR), add startTime and endTime
            $now = date('Y-m-d\TH:i:s');
            $resource->updateRow($job->jobId, array("startTime" => $now, "endTime" => $now));

            return;
        }

        // submit query

        // first get the creationTime from UWSJobs (pending job list),
        // because we want to store it in the main table
        $resource = new Uws_Model_Resource_UWSJobs();
        $jobUws = $resource->fetchRow($job->jobId);
        $creationTime = $jobUws['creationTime'];


        if ($this->getResource()->hasQueues()) {
            $response = $model->query($sql, false, $tablename, $sources, array("queue" => $queue, "jobId" => $job->jobId), $creationTime, 'uws');
        } else {
            $response = $model->query($sql, false, $tablename, $sources, array("jobId" => $job->jobId), $creationTime, 'uws');
        }

        if ($response['status'] !== 'ok') {
            // throw error
            foreach ($response['errors'] as $error) {
                $job->addError($error);
            }
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ERROR", "errorSummary" => Zend_Json::encode($job->errorSummary)));

            // job is in final state now (ERROR), add startTime and endTime
            $now = date('Y-m-d\TH:i:s');
            $resource->updateRow($job->jobId, array("startTime" => $now, "endTime" => $now));

            return;
        }

        // clean up stuff (basically just remove the job in the temporary UWS job store - if we are here
        // everything has been handled by the queue)
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
