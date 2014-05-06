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

class Meetings_ParticipantStatusController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_ParticipantStatus');
    }

    public function indexAction() {
        $this->getControllerHelper('table', array('object' => 'participant status'))->index();
    }

    public function showAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('table')->show($id);
    }

    public function createAction() {
        $this->getControllerHelper('form', array('title' => 'Create participant status'))->create();
    }

    public function updateAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('form', array('title' => 'Update participant status'))->update($id);
    }

    public function deleteAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('form', array('title' => 'Delete participant status'))->delete($id);
    }

}
