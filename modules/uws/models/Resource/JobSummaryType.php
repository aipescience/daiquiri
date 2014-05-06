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

/**
 * XSD class ShortJobDescription
 */
class Uws_Model_Resource_JobSummaryType extends Uws_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct($name) {
        parent::__construct();

        $this->name = $name;

        $this->jobId = false;
        $this->runId = false;
        $this->ownerId = false;
        $this->phase = "PENDING";
        $this->quote = false;
        $this->startTime = false;
        $this->endTime = false;
        $this->executionDuration = 0;
        $this->destruction = false;
        $this->parameters = array();
        $this->results = array();
        $this->errorSummary = false;
        $this->jobInfo = array();
    }

    public function addParameter($id, $value, $byReference = false, $isPost = false) {
        $newParam = new Uws_Model_Resource_Parameter();

        $newParam->id = $id;
        $newParam->value = $value;
        $newParam->byReference = $byReference;
        $newParam->isPost = $isPost;

        $this->parameters[$id] = $newParam;
    }

    public function addResult($id, $href) {
        $newRes = new Uws_Model_Resource_ResultReferenceType("result");

        $newRes->id = $id;
        $newRes->reference->href = $href;

        $this->results[] = $newRes;
    }

    public function addError($message) {
        if ($this->errorSummary === false) {
            $this->errorSummary = new Uws_Model_Resource_ErrorSummary();
        }

        $this->errorSummary->messages[] = $message;
    }

    public function resetErrors() {
        $this->errorSummary = new Uws_Model_Resource_ErrorSummary();
    }

    public function parametersToJSON() {
        $tmpArray = array();

        foreach ($this->parameters as $param) {
            $tmpArray[] = array("id" => $param->id, "value" => $param->value,
                "byReference" => $param->byReference, "isPost" => $param->isPost);
        }

        return Zend_Json::encode($tmpArray);
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $job = $xmlDoc->createElementNS($this->nsUws, "uws:{$this->name}");
        $xmlDoc->appendChild($job);

        $this->_writeXMLElement($xmlDoc, $job, "jobId");
        $this->_writeXMLElement($xmlDoc, $job, "runId", true);
        $this->_writeXMLElement($xmlDoc, $job, "ownerId");
        $this->_writeXMLElement($xmlDoc, $job, "phase");
        $this->_writeXMLElement($xmlDoc, $job, "quote");
        $this->_writeXMLElement($xmlDoc, $job, "startTime");
        $this->_writeXMLElement($xmlDoc, $job, "endTime");
        $this->_writeXMLElement($xmlDoc, $job, "executionDuration");
        $this->_writeXMLElement($xmlDoc, $job, "destruction");

        $parameters = $xmlDoc->createElementNS($this->nsUws, "uws:parameters");
        $job->appendChild($parameters);

        foreach ($this->parameters as $parameter) {
            $parameter->toXML($xmlDoc, $parameters);
        }

        $results = $xmlDoc->createElementNS($this->nsUws, "uws:results");
        $job->appendChild($results);

        foreach ($this->results as $result) {
            $result->toXML($xmlDoc, $results);
        }

        if ($this->errorSummary !== false) {
            $this->errorSummary->toXML($xmlDoc, $job);
        }

        if ($this->validateSchema == true) {
            if ($xmlDoc->schemaValidate("http://www.ivoa.net/xml/UWS/v1.0") === false) {
                throw new Exception("UWS JobSummary: XML is not a valid UWS schema");
            }
        }
    }

    private function _writeXMLElement(&$xmlDoc, &$node, $name, $notNill = false) {
        if (!isset($this->$name)) {
            $this->$name = false;
        }

        if ($this->$name === false && $notNill === false) {
            $element = $xmlDoc->createElementNS($this->nsUws, "uws:{$name}");
            $null = $xmlDoc->createAttributeNS($this->nsXsi, "xsi:nil");
            $null->value = "true";
            $element->appendChild($null);
        } else if ($this->$name === false && $notNill === true) {
            return;
        } else {
            $element = $xmlDoc->createElementNS($this->nsUws, "uws:{$name}", htmlspecialchars($this->$name, ENT_QUOTES));
        }

        if ($node === false) {
            $xmlDoc->appendChild($element);
        } else {
            $node->appendChild($element);
        }
    }

}

