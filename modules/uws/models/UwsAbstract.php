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

// @TODO: LIMITATIONS: Parameters by reference are not yet supported!!
// @TODO: LIMITATIONS: Parameters by posting files are not yet supported!!

abstract class Uws_Model_UwsAbstract extends Daiquiri_Model_Abstract {

    protected static $status = array('PENDING', 'QUEUED', 'EXECUTING', 'COMPLETED', 'ERROR', 'ABORTED', 'UNKNOWN', 'HELD', 'SUSPENDED');

    public function __construct() {
        parent::__construct();
    }

    public function index($requestParams) {
        $xmlDoc = new DOMDocument('1.0', "UTF-8");
        $xmlDoc->formatOutput = true;

        //build job list for current user
        $jobs = $this->getJobList($requestParams);

        //@TODO: REMOVE THIS DEBUG FLAG!
        $jobs->validateSchema = false;

        $jobs->toXML($xmlDoc);

        return array(
            'status' => 'ok',
            'body' => $xmlDoc->saveXML(),
            'headers' => array('Content-Type' => 'application/xml; charset=utf-8')
        );
    }

    public function get($requestParams) {
        // is this job pending?
        $job = $this->getPendingJob($requestParams['wild0']);

        if ($job === false) {
            // get job information for this id
            $job = $this->getJob($requestParams);
        }

        if ($job === false) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get the wildcard parameters
        $params = array();
        foreach ($requestParams as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $params[$key] = $value;
            }
        }

        if (count($params) == 1) {
            //this is just a get on the object
            $xmlDoc = new DOMDocument('1.0', "UTF-8");
            $xmlDoc->formatOutput = true;

            //@TODO: REMOVE THIS DEBUG FLAG!
            $job->validateSchema = false;

            $job->toXML($xmlDoc);

            return array(
                'status' => 'ok',
                'body' => $xmlDoc->saveXML(),
                'headers' => array('Content-Type' => 'application/xml; charset=utf-8')
            );

        } else {
            $action = $params['wild1'];

            switch ($action) {
                case 'phase':
                    return array(
                        'status' => 'ok',
                        'body' => $job->phase,
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                case 'executionduration':
                    return array(
                        'status' => 'ok',
                        'body' => $job->executionDuration,
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                case 'destruction':
                    return array(
                        'status' => 'ok',
                        'body' => $job->destruction,
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                case 'error':
                    $errorInfo = $this->getError($job);
                    return array(
                        'status' => 'ok',
                        'body' => $errorInfo,
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                case 'quote':
                    return array(
                        'status' => 'ok',
                        'body' => $job->quote,
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                case 'results':
                    // this is just a get on the object
                    $xmlDoc = new DOMDocument('1.0', "UTF-8");
                    $xmlDoc->formatOutput = true;

                    //@TODO: REMOVE THIS DEBUG FLAG!
                    $job->validateSchema = false;

                    $results = $xmlDoc->createElementNS($job->nsUws, "uws:results");
                    $xmlDoc->appendChild($results);

                    foreach ($job->results as $result) {
                        $result->toXML($xmlDoc, $results);
                    }

                    return array(
                        'status' => 'ok',
                        'body' => $xmlDoc->saveXML(),
                        'headers' => array('Content-Type' => 'application/xml; charset=utf-8')
                    );
                    break;
                case 'parameters':
                    //this is just a get on the object
                    $xmlDoc = new DOMDocument('1.0', "UTF-8");
                    $xmlDoc->formatOutput = true;

                    //@TODO: REMOVE THIS DEBUG FLAG!
                    $job->validateSchema = false;

                    $parameters = $xmlDoc->createElementNS($job->nsUws, "uws:parameters");
                    $xmlDoc->appendChild($parameters);

                    foreach ($job->parameters as $parameter) {
                        $parameter->toXML($xmlDoc, $parameters);
                    }

                    return array(
                        'status' => 'ok',
                        'body' => $xmlDoc->saveXML(),
                        'headers' => array('Content-Type' => 'application/xml; charset=utf-8')
                    );
                    break;
                case 'owner':
                    return array(
                        'status' => 'ok', 
                        'body' => $job->ownerId, 
                        'headers' => array('Content-Type' => 'text/plain;')
                    );
                    break;
                default:
                    throw new Daiquiri_Exception_NotFound();
                    break;
            }
        }
    }

    public function post($requestParams, $postParams) {
        // get the wildcard parameters
        $wildParams = array();
        foreach ($requestParams as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $wildParams[$key] = $value;
            }
        }

        if (count($wildParams) == 0) {
            // create a new job
            // getting rid of everything that is not directly related with the POST parameters
            unset($postParams['modulename']);
            unset($postParams['module']);
            unset($postParams['controller']);
            unset($postParams['action']);

            $phase = false;
            if (isset($postParams['phase'])) {
                $phase = $postParams['phase'];
                unset($postParams['phase']);
            }

            $job = $this->createPendingJob($postParams, $this->createJobId());

            if (strtolower($phase) === "run") {
                $this->runJob($job);
            }
        } else {
            // is this job pending?
            $job = $this->getPendingJob($wildParams['wild0']);

            if ($job === false) {
                // get job information for this id
                $job = $this->getJob($requestParams);
            }

            if ($job === false) {
                throw new Daiquiri_Exception_NotFound();
            }

            if (!isset($wildParams['wild1'])) {
                // this is a POST to the job and might be a request to add more data to the
                // params part...
                unset($postParams['modulename']);
                unset($postParams['module']);
                unset($postParams['controller']);
                unset($postParams['action']);
                unset($postParams['wild0']);

                // check if this is a phase change
                $phase = false;
                if (isset($postParams['phase'])) {
                    $phase = $postParams['phase'];
                    unset($postParams['phase']);
                }

                // allow for ACTION=DELETE
                if (isset($postParams['action'])) {
                    $phase = $postParams['action'];
                    unset($postParams['action']);
                }

                if (strtolower($phase) === "run") {
                    $this->runJob($job);
                } else if (strtolower($phase) === "abort") {
                    $this->abortJob($job);
                } else if (strtolower($phase) === "delete") {
                    $this->deleteJob($job);
                } else {
                    if (isset($postParams['destruction'])) {
                        $this->setDestructTime($job, $postParams['destruction']);
                        unset($postParams['destruction']);
                    }

                    if (isset($postParams['executionduration'])) {
                        $this->setExecutionDuration($job, $postParams['executionduration']);
                        unset($postParams['executionduration']);
                    }

                    $this->setParameters($job, $postParams);
                }
            } else {
                $action = $wildParams['wild1'];

                switch ($action) {
                    case 'destruction':
                        if (isset($postParams['destruction'])) {
                            $this->setDestructTime($job, $postParams['destruction']);
                        } else {
                            throw new Daiquiri_Exception_BadRequest();
                        }

                        break;

                    case 'executionduration':
                        if (isset($postParams['executionduration'])) {
                            $this->setExecutionDuration($job, $postParams['executionduration']);
                        } else {
                            throw new Daiquiri_Exception_BadRequest();
                        }

                        break;

                    case 'parameters':
                        // this is another path that might be foreseeable to set parameters (as mentioned in the
                        // standard)
                        // check if everything is sane
                        if (isset($postParams['wild2'])) {
                            throw new Daiquiri_Exception_BadRequest();
                        }

                        unset($postParams['modulename']);
                        unset($postParams['module']);
                        unset($postParams['controller']);
                        unset($postParams['action']);
                        unset($postParams['wild0']);
                        unset($postParams['wild1']);
                        unset($postParams[$job->jobId]);

                        $this->setParameters($job, $postParams);
                        break;

                    default:
                        throw new Daiquiri_Exception_BadRequest();
                        break;
                }
            }
        }
        return array(
            'status' => 'ok',
            'jobId' => $job->jobId
        );
    }

    public function put($requestParams, $putParams, $rawBody) {
        // get the wildcard parameters
        $wildParams = array();
        foreach ($requestParams as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $wildParams[$key] = $value;
            }
        }

        if (count($wildParams) !== 3) {
            throw new Daiquiri_Exception_BadRequest();
        }

        // is this job pending?
        $job = $this->getPendingJob($wildParams['wild0']);

        if ($job === false) {
            // get job information for this id
            $job = $this->getJob($requestParams);
        }

        if ($job === false) {
            throw new Daiquiri_Exception_NotFound();
        }

        $action = $wildParams['wild1'];

        switch ($action) {
            case 'parameters':
                // this is another path that might be foreseeable to set parameters 
                // (as mentioned in the standard)
                $newParam = array();
                $newParam[$putParams['wild2']] = $rawBody;
                $this->setParameters($job, $newParam);
                break;
            default:
                throw new Daiquiri_Exception_BadRequest();
                break;
        }

        return array(
            'status' => 'ok',
            'jobId' => $job->jobId
        );
    }

    public function delete($requestParams) {
        // get the wildcard parameters
        $wildParams = array();
        foreach ($requestParams as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $wildParams[$key] = $value;
            }
        }

        if (count($wildParams) !== 1) {
            throw new Daiquiri_Exception_BadRequest();
        }

        // is this job pending?
        $job = $this->getPendingJob($wildParams['wild0']);

        if ($job === false) {
            // get job information for this id
            $job = $this->getJob($requestParams);
        }

        if ($job === false) {
            throw new Daiquiri_Exception_NotFound();
        }

        $this->deleteJob($job);

        return array(
            'status' => 'ok'
        );
    }

    public function options() {
        return array(
            'stautus' => 'ok',
            'headers' => array('Allow' => 'OPTIONS, INDEX, GET, POST, PUT, DELETE')
        );
    }

    abstract public function getJobList($params);

    abstract public function getJob($params);

    //The UWS standard allows for either brief summary errors or larger reports
    //such as stack traces or whatever. This function should provide the extended
    //error report (if applicable, otherwise just the error string) for reporting
    //by URI:/{jobs}/(job-id)/error
    abstract public function getError(Uws_Model_Resource_JobSummaryType $job);

    abstract public function getQuote();

    //this is the implementation of the setDestructTime. If the saving of the data for the pending temporary
    //job is handeled by this abstract class and not the implementation, check for phase === "PENDING" in the
    //implementation and just set the value in the job object without saving. Otherwise do whatever you like.
    abstract public function setDestructTimeImpl(Uws_Model_Resource_JobSummaryType &$job, $newDestructTime);

    //this is the implementation of the setExecutionDuration. If the saving of the data for the pending temporary
    //job is handeled by this abstract class and not the implementation, check for phase === "PENDING" in the
    //implementation and just set the value in the job object without saving. Otherwise do whatever you like.
    abstract public function setExecutionDurationImpl(Uws_Model_Resource_JobSummaryType &$job, $newExecutionDuration);

    //this is the implementation of the deleteJob method on the implementation side. Similar to setDestructTimeImpl
    //above
    abstract public function deleteJobImpl(Uws_Model_Resource_JobSummaryType &$job);

    //this is the implementation of the deleteJob method on the implementation side. Similar to setDestructTimeImpl
    //above
    abstract public function abortJobImpl(Uws_Model_Resource_JobSummaryType &$job);

    //run this job - set it to QUEUED and let the magic happen
    abstract public function runJob(Uws_Model_Resource_JobSummaryType &$job);

    //this function handles the switch between the pending temporary job and the one used by the 
    //implementation (if the job was retrieved by the implementation). If it does, override
    //this function with whatever the implementation needs
    public function setDestructTime(Uws_Model_Resource_JobSummaryType &$job, $newDestructTime) {
        if (isset($job->handledByAbstract)) {
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("destruction" => $newDestructTime));
        } else {
            $this->setDestructTimeImpl($job, $newDestructTime);
        }
    }

    //this function handles the switch between the pending temporary job and the one used by the 
    //implementation (if the job was retrieved by the implementation). If it does, override
    //this function with whatever the implementation needs
    public function setExecutionDuration(Uws_Model_Resource_JobSummaryType &$job, $newExecutionDuration) {
        if (isset($job->handledByAbstract)) {
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("executionDuration" => $newExecutionDuration));
        } else {
            $this->setExecutionDurationImpl($job, $newExecutionDuration);
        }
    }

    public function setParameters(Uws_Model_Resource_JobSummaryType &$job, array $paramArray) {
        //make list of available parameters
        $parameters = array();
        foreach ($job->parameters as $param) {
            $parameters[] = $param->id;
        }

        foreach ($paramArray as $key => $value) {
            if (isset($job->parameters[$key])) {
                $job->parameters[$key]->value = $value;
            } else {
                $job->addParameter($key, $value);
            }
        }

        //save to database
        $resource = new Uws_Model_Resource_UWSJobs();
        $resource->updateRow($job->jobId, array("parameters" => $job->parametersToJSON()));
    }

    //this function creates a pending job in the UWS_Jobs table that will be further
    //processed by either the implementation or the default UWS behaviour
    //RETURNS a job object
    public function createPendingJob($postParams, $id) {
        $postParams = array_change_key_case($postParams, CASE_LOWER);

        //create new job object
        $jobUWS = new Uws_Model_Resource_JobSummaryType("job");

        $jobUWS->jobId = $id;
        $jobUWS->ownerId = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $jobUWS->phase = "PENDING";

        $jobUWS->startTime = false;
        $jobUWS->endTime = false;

        $jobUWS->quote = $this->getQuote();

        //no destruction time supported, so return hillariously high number
        $datetime = new DateTime('31 Dec 2999');
        $jobUWS->destruction = $datetime->format('c');

        //setting anything we already understand and belongs to the UWS
        if (isset($postParams['destruction'])) {
            $jobUWS->destruction = $postParams['destruction'];
            unset($postParams['destruction']);
        }

        if (isset($postParams['executionduration'])) {
            $jobUWS->executionDuration = $postParams['executionduration'];
            unset($postParams['executionduration']);
        }

        if (isset($postParams['runid'])) {
            $jobUWS->runId = $postParams['runid'];
            unset($postParams['runid']);
        }

        //fill the parameter part of the UWS with the original information stored in the queue
        foreach ($postParams as $key => $param) {
            $jobUWS->addParameter($key, $param);
        }

        //now save the job in the pending job database
        $job = array();
        $job['jobId'] = $jobUWS->jobId;
        $job['runId'] = $jobUWS->runId;
        $job['ownerId'] = $jobUWS->ownerId;
        $job['phase'] = $jobUWS->phase;
        $job['quote'] = $jobUWS->quote;
        $job['startTime'] = $jobUWS->startTime;
        $job['endTime'] = $jobUWS->endTime;
        $job['executionDuration'] = $jobUWS->executionDuration;
        $job['destruction'] = $jobUWS->destruction;
        $job['parameters'] = $jobUWS->parametersToJSON();
        $job['results'] = NULL;
        $job['errorSummary'] = NULL;
        $job['jobInfo'] = NULL;

        //get resource and save
        $resource = new Uws_Model_Resource_UWSJobs();
        $resource->insertRow($job);

        return $jobUWS;
    }

    //can be overridden
    public function createJobId() {
        //calculate unique (hopefully) id for this job
        $id = microtime(true) * 1000;
        $id = intval($id);

        //shift by 8 bits to make some space for a random component
        $id = $id << 8;

        //add the last 8 bits of a random number to the id
        $id += mt_rand() & 0x000000ff;

        return $id;
    }

    //optains a (pending) job in the UWS job list
    public function getPendingJob($id) {
        $resource = new Uws_Model_Resource_UWSJobs();
        $job = $resource->fetchObjectWithId($id);

        if ($job === false) {
            return false;
        } else {
            $job->handledByAbstract = true;

            return $job;
        }
    }

    //deletes the job - if this is job is handled by the abstract class, just delete
    //it, otherwise pass the request down to the implementation
    public function deleteJob(Uws_Model_Resource_JobSummaryType &$job) {
        if (isset($job->handledByAbstract)) {
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->deleteRow($job->jobId);
        } else {
            $this->deleteJobImpl($job);
        }
    }

    //aborts the job - if this is job is handled by the abstract class, just aborts
    //it, otherwise pass the request down to the implementation
    public function abortJob(Uws_Model_Resource_JobSummaryType &$job) {
        if (isset($job->handledByAbstract)) {
            $resource = new Uws_Model_Resource_UWSJobs();
            $resource->updateRow($job->jobId, array("phase" => "ABORTED"));
        } else {
            $this->abortJobImpl($job);
        }
    }

}