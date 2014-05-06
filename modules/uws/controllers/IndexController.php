<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

class Uws_IndexController extends Daiquiri_Controller_AbstractREST {

    public function init() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');

        $params = $this->_getAllParams();

        if (!isset($params['moduleName'])) {
            $this->_sendHttpError(404);
        }

        $this->_model = Daiquiri_Proxy::factory(ucfirst($params['moduleName']) . '_Model_Uws');

        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
    }

    public function indexAction() {
        $xmlDoc = new DOMDocument('1.0', "UTF-8");
        $xmlDoc->formatOutput = true;

        //build job list for current user
        $jobs = $this->_model->getJobList($this->_getAllParams());

        //@TODO: REMOVE THIS DEBUG FLAG!
        $jobs->validateSchema = false;

        $jobs->toXML($xmlDoc);

        $this->getResponse()->setHeader('Content-Type', 'application/xml;');
        $this->getResponse()->appendBody($xmlDoc->saveXML());
    }

    public function getAction() {
        $requestParams = $this->_getAllParams();

        //is this job pending?
        $job = $this->_model->getPendingJob($requestParams['wild0']);

        if ($job === false) {
            //get job information for this id
            $job = $this->_model->getJob($requestParams);
        }

        if ($job === false) {
            $this->_sendHttpError(403);
        }

        //get the wildcard parameters
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

            $this->getResponse()->setHeader('Content-Type', 'application/xml;');
            $this->getResponse()->appendBody($xmlDoc->saveXML());
        } else {
            $action = $params['wild1'];

            switch ($action) {
                case 'phase':
                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($job->phase);
                    break;
                case 'executionduration':
                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($job->executionDuration);
                    break;
                case 'destruction':
                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($job->destruction);
                    break;
                case 'error':
                    $errorInfo = $this->_model->getError($job);

                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($errorInfo);
                    break;
                case 'quote':
                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($job->quote);
                    break;
                case 'results':
                    //this is just a get on the object
                    $xmlDoc = new DOMDocument('1.0', "UTF-8");
                    $xmlDoc->formatOutput = true;

                    //@TODO: REMOVE THIS DEBUG FLAG!
                    $job->validateSchema = false;

                    $results = $xmlDoc->createElementNS($job->nsUws, "uws:results");
                    $xmlDoc->appendChild($results);

                    foreach ($job->results as $result) {
                        $result->toXML($xmlDoc, $results);
                    }

                    $this->getResponse()->setHeader('Content-Type', 'application/xml;');
                    $this->getResponse()->appendBody($xmlDoc->saveXML());
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

                    $this->getResponse()->setHeader('Content-Type', 'application/xml;');
                    $this->getResponse()->appendBody($xmlDoc->saveXML());
                    break;
                case 'owner':
                    $this->getResponse()->setHeader('Content-Type', 'text/plain;');
                    $this->getResponse()->appendBody($job->ownerId);
                    break;
                default:
                    $this->_sendHttpError(404);
                    break;
            }
        }
    }

    public function postAction() {
        //get the wildcard parameters
        $params = array();
        foreach ($this->_getAllParams() as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $params[$key] = $value;
            }
        }

        $postParams = $this->getRequest()->getParams();
        $postParams = array_change_key_case($postParams, CASE_LOWER);

        if (count($params) == 0) {
            //create a new job
            //getting rid of everything that is not directly related with the POST parameters
            unset($postParams['modulename']);
            unset($postParams['module']);
            unset($postParams['controller']);
            unset($postParams['action']);

            $phase = false;
            if (isset($postParams['phase'])) {
                $phase = $postParams['phase'];
                unset($postParams['phase']);
            }

            $job = $this->_model->createPendingJob($postParams, $this->_model->createJobId());

            if (strtolower($phase) === "run") {
                $this->_model->runJob(&$job);
            }
        } else {
            //is this job pending?
            $job = $this->_model->getPendingJob($params['wild0']);

            if ($job === false) {
                //get job information for this id
                $job = $this->_model->getJob($this->_getAllParams());
            }

            if ($job === false) {
                $this->_sendHttpError(403);
            }

            if (!isset($params['wild1'])) {
                //this is a POST to the job and might be a request to add more data to the
                //params part...
                unset($postParams['modulename']);
                unset($postParams['module']);
                unset($postParams['controller']);
                unset($postParams['action']);
                unset($postParams['wild0']);

                //check if this is a phase change
                $phase = false;
                if (isset($postParams['phase'])) {
                    $phase = $postParams['phase'];
                    unset($postParams['phase']);
                }

                //allow for ACTION=DELETE
                if (isset($postParams['action'])) {
                    $phase = $postParams['action'];
                    unset($postParams['action']);
                }

                if (strtolower($phase) === "run") {
                    $this->_model->runJob(&$job);
                } else if (strtolower($phase) === "abort") {
                    $this->_model->abortJob(&$job);
                } else if (strtolower($phase) === "delete") {
                    $this->_model->deleteJob(&$job);
                } else {
                    if (isset($postParams['destruction'])) {
                        $this->_model->setDestructTime(&$job, $postParams['destruction']);
                        unset($postParams['destruction']);
                    }

                    if (isset($postParams['executionduration'])) {
                        $this->_model->setExecutionDuration(&$job, $postParams['executionduration']);
                        unset($postParams['executionduration']);
                    }

                    $this->_model->setParameters(&$job, $postParams);
                }
            } else {
                $action = $params['wild1'];

                switch ($action) {
                    case 'destruction':
                        if (isset($postParams['destruction'])) {
                            $this->_model->setDestructTime(&$job, $postParams['destruction']);
                        } else {
                            $this->_sendHttpError(403);
                        }

                        break;

                    case 'executionduration':
                        if (isset($postParams['executionduration'])) {
                            $this->_model->setExecutionDuration(&$job, $postParams['executionduration']);
                        } else {
                            $this->_sendHttpError(403);
                        }

                        break;

                    case 'parameters':
                        //this is another path that might be foreseeable to set parameters (as mentioned in the
                        //standard)
                        //check if everything is sane
                        if (isset($postParams['wild2'])) {
                            $this->_sendHttpError(404);
                        }

                        unset($postParams['modulename']);
                        unset($postParams['module']);
                        unset($postParams['controller']);
                        unset($postParams['action']);
                        unset($postParams['wild0']);
                        unset($postParams['wild1']);
                        unset($postParams[$job->jobId]);

                        $this->_model->setParameters(&$job, $postParams);
                        break;

                    default:
                        $this->_sendHttpError(404);
                        break;
                }
            }
        }

        //now that the job has been initialised, rerout the request by sending 303 and the
        //location
        $requestParams = $this->_getAllParams();

        $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                "/uws/" . urlencode($requestParams['moduleName']) . "/" . urlencode($job->jobId);

        $this->getResponse()
                ->clearHeaders()
                ->setHttpResponseCode(303)
                ->setHeader('Location', $href)
                ->sendResponse();

        die(0);
    }

    public function putAction() {
        //get the wildcard parameters
        $params = array();
        foreach ($this->_getAllParams() as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $params[$key] = $value;
            }
        }

        $putParams = $this->getRequest()->getParams();

        if (count($params) !== 3) {
            $this->_sendHttpError(404);
        }

        //is this job pending?
        $job = $this->_model->getPendingJob($params['wild0']);

        if ($job === false) {
            //get job information for this id
            $job = $this->_model->getJob($this->_getAllParams());
        }

        if ($job === false) {
            $this->_sendHttpError(403);
        }

        $action = $params['wild1'];

        switch ($action) {
            case 'parameters':
                //this is another path that might be foreseeable to set parameters (as mentioned in the
                //standard)
                $newParam = array();
                $newParam[$putParams['wild2']] = $this->getRequest()->getRawBody();

                $this->_model->setParameters(&$job, $newParam);
                break;

            default:
                $this->_sendHttpError(404);
                break;
        }

        //now that the job has been initialised, rerout the request by sending 303 and the
        //location
        $requestParams = $this->_getAllParams();

        $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                "/uws/" . urlencode($requestParams['moduleName']) . "/" . urlencode($job->jobId);

        $this->getResponse()
                ->clearHeaders()
                ->setHttpResponseCode(303)
                ->setHeader('Location', $href)
                ->sendResponse();

        die(0);
    }

    public function deleteAction() {
        $requestParams = $this->_getAllParams();

        //is this job pending?
        $job = $this->_model->getPendingJob($requestParams['wild0']);

        if ($job === false) {
            //get job information for this id
            $job = $this->_model->getJob($requestParams);
        }

        if ($job === false) {
            $this->_sendHttpError(403);
        }

        //get the wildcard parameters
        $params = array();
        foreach ($requestParams as $key => $value) {
            if (strstr($key, "wild") !== false) {
                $params[$key] = $value;
            }
        }

        if (count($params) !== 1) {
            $this->_sendHttpError(404);
        }

        $this->_model->deleteJob(&$job);

        //now that the job has been initialised, rerout the request by sending 303 and the
        //location
        $requestParams = $this->_getAllParams();

        $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                "/uws/" . urlencode($requestParams['moduleName']);

        $this->getResponse()
                ->clearHeaders()
                ->setHttpResponseCode(303)
                ->setHeader('Location', $href)
                ->sendResponse();

        die(0);
    }

    public function headAction() {
        $this->getResponse()->setBody(null);
    }

    public function optionsAction() {
        $this->getResponse()->setBody(null);
        $this->getResponse()->setHeader('Allow', 'OPTIONS, INDEX, GET, POST, PUT, DELETE');
    }

}