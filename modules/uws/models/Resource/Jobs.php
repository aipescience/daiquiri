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
 * XSD class reference
 */
class Uws_Model_Resource_Jobs extends Uws_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        parent::__construct();

        $this->jobref = array();
    }

    public function addJob($id, $href, array $phases) {
        $newJob = new Uws_Model_Resource_ShortJobDescriptionType("jobref");

        $newJob->id = $id;
        $newJob->reference->href = $href;
        $newJob->phase = $phases;

        $this->jobref[] = $newJob;
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $root = $xmlDoc->createElementNS($this->nsUws, "uws:jobs");
        $xmlDoc->appendChild($root);

        foreach ($this->jobref as $job) {
            $job->toXML($xmlDoc, $root);
        }

        if ($this->validateSchema == true) {
            if ($xmlDoc->schemaValidate("http://www.ivoa.net/xml/UWS/v1.0") === false) {
                throw new Exception("UWS Jobs: XML is not a valid UWS schema");
            }
        }
    }

}
