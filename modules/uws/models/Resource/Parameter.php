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
class Uws_Model_Resource_Parameter extends Uws_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        parent::__construct();

        $this->id = false;
        $this->byReference = false;
        $this->isPost = false;

        $this->value = false;
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $parameter = $xmlDoc->createElementNS($this->nsUws, "uws:parameter", htmlspecialchars($this->value, ENT_QUOTES));

        if ($this->id !== false) {
            $id = $xmlDoc->createAttribute("id");
            $id->value = $this->id;
            $parameter->appendChild($id);
        }

        if ($this->byReference === true) {
            $byReference = $xmlDoc->createAttribute("byReference");
            $byReference->value = "true";
            $parameter->appendChild($byReference);
        }

        if ($this->isPost === true) {
            $isPost = $xmlDoc->createAttribute("isPost");
            $isPost->value = "true";
            $parameter->appendChild($isPost);
        }

        if ($node === false) {
            $xmlDoc->appendChild($parameter);
        } else {
            $node->appendChild($parameter);
        }
    }

}

