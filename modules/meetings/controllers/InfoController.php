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

class Meetings_InfoController extends Daiquiri_Controller_Abstract {

    protected $_model;

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
