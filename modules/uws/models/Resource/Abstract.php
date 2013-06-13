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
abstract class Uws_Model_Resource_Abstract extends Daiquiri_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        $this->nsUws = "http://www.ivoa.net/xml/UWS/v1.0";
        $this->nsXLink = "http://www.w3.org/1999/xlink";
        $this->nsXsi = "http://www.w3.org/2001/XMLSchema-instance";
        $this->validateSchema = false;
    }

    abstract public function toXML(&$xmlDoc, &$node = false);
}
