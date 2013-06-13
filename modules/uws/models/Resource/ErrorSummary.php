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
 * XSD class ShortJobDescription
 */
class Uws_Model_Resource_ErrorSummary extends Uws_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        parent::__construct();

        $this->type = "transient";
        $this->hasDetail = false;
        $this->messages = array();
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $error = $xmlDoc->createElementNS($this->nsUws, "uws:errorSummary", "");

        $type = $xmlDoc->createAttribute("type");
        $type->value = $this->type;
        $error->appendChild($type);

        if ($this->hasDetail === true) {
            $hasDetail = $xmlDoc->createAttribute("hasDetail");
            $hasDetail->value = "true";
            $error->appendChild($hasDetail);
        } else {
            $hasDetail = $xmlDoc->createAttribute("hasDetail");
            $hasDetail->value = "false";
            $error->appendChild($hasDetail);
        }

        foreach ($this->messages as $message) {
            $messageElement = $xmlDoc->createElementNS($this->nsUws, "uws:message", $message);
            $error->appendChild($messageElement);
        }

        if ($node === false) {
            $xmlDoc->appendChild($error);
        } else {
            $node->appendChild($error);
        }
    }

}

