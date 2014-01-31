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

class Meetings_Model_ContributionTypes extends Daiquiri_Model_Table {

    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Simple');
        $this->getResource()->setTablename('Meetings_ContributionTypes');
    }

    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    public function create(array $formParams = array()) {
        return $this->getModelHelper('CRUD')->create($formParams);
    }

    public function update($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->update($id, $formParams);
    }

    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }
}