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

class Meetings_InfoController extends Daiquiri_Controller_AbstractCRUD {

    public function init() {
        $this->_meetingId = $this->_getParam('meetingId');
        if ($this->_meetingId === null) {
            throw new Exception('$meetingId not provided in ' . get_class($this) . '::init()');
        }
    }

    public function participantsAction() {
        $model = Daiquiri_Proxy::factory('Meetings_Model_Participants');
        $response = $model->info($this->_meetingId);

        // assign to view
        $this->setViewElements($response);
    }

    public function contributionsAction() {
        $model = Daiquiri_Proxy::factory('Meetings_Model_Contributions');
        $response = $model->info($this->_meetingId);

        // assign to view
        $this->setViewElements($response);
    }
}
