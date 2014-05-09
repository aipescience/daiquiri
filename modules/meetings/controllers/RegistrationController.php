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

class Meetings_RegistrationController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Meetings_Model_Registration');
    }

    public function indexAction() {
        $response = $this->_model->index();
        $this->view->assign($response);
    }

    public function showAction() {
        $id = $this->getParam('id');
        $response = $this->_model->show($id);
        $this->view->redirect = $this->_getParam('redirect','/meetings/registration/');
        $this->view->assign($response);
    }

    public function deleteAction() {
        $id = $this->getParam('id');
        $this->getControllerHelper('form', array('title' => 'Delete registration'))->delete($id);
    }

    public function registerAction() {
        // get params
        $redirect = $this->_getParam('redirect', '/');
        $meetingId = $this->_getParam('meetingId');
        if ($meetingId === null) {
            $response = array('status' => 'error', 'errors' => array('The MeetingId is not specified.'));
        } else {
            // check if POST or GET
            if ($this->_request->isPost()) {
                if ($this->_getParam('cancel')) {
                    // user clicked cancel
                    $this->_redirect($redirect);
                } else {
                    // validate form and do stuff
                    $response = $this->_model->register($meetingId, $this->_request->getPost());
                }
            } else {
                // just display the form
                $response = $this->_model->register($meetingId);
            }

            // set action for form
            $this->setFormAction($response, '/meetings/registration/register?meetingId=' . $meetingId);
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->assign($response);
    }

    public function validateAction() {
        // get params from request
        $id = $this->_getParam('id');
        $code = $this->_getParam('code');

        $response = $this->_model->validate($id, $code);

        // assign to view
        $this->view->assign($response);
    }
}
