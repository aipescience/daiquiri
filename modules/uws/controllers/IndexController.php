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

class Uws_IndexController extends Daiquiri_Controller_Abstract {

    public function init() {
        $requestParams = $this->_getAllParams();

        if (empty($requestParams['moduleName'])) {
            throw new Daiquiri_Exception_BadRequest();
        } else {
            $this->_model = Daiquiri_Proxy::factory(ucfirst($requestParams['moduleName']) . '_Model_Uws');

            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $this->getResponse()->clearBody();
            $this->getResponse()->clearHeaders();
        }
    }

    public function indexAction() {
        $response = $this->_model->index($this->_getAllParams());
        foreach ($response['headers'] as $key => $value) {
            $this->getResponse()->setHeader($key, $value);
        }
        $this->getResponse()->appendBody($response['body']);
    }

    public function getAction() {
        $response = $this->_model->get($this->_getAllParams());
        foreach ($response['headers'] as $key => $value) {
            $this->getResponse()->setHeader($key, $value);
        }
        $this->getResponse()->appendBody($response['body']);
    }

    public function postAction() {
        $requestParams = $this->_getAllParams();
        $postParams = $this->getRequest()->getParams();
        $postParams = array_change_key_case($postParams, CASE_LOWER);

        $response = $this->_model->post($requestParams, $postParams);

        // now that the job has been initialised, reroute the request by sending 303
        $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($requestParams['moduleName']) . "/" . urlencode($response['jobId']);

        $this->getResponse()
            ->clearHeaders()
            ->setHttpResponseCode(303)
            ->setHeader('Location', $href)
            ->sendResponse();

        die(0);
    }

    public function putAction() {
        $requestParams = $this->_getAllParams();
        $putParams = $this->getRequest()->getParams();
        $putParams = array_change_key_case($putParams, CASE_LOWER);
        $rawBody = $this->getRequest()->getRawBody();

        $response = $this->_model->put($requestParams, $putParams, $rawBody);

        // now that the job has been initialised, reroute the request by sending 303
        $href = Daiquiri_Config::getInstance()->getSiteUrl() . "/uws/" . urlencode($requestParams['moduleName']) . "/" . urlencode($response['jobId']);

        $this->getResponse()
            ->clearHeaders()
            ->setHttpResponseCode(303)
            ->setHeader('Location', $href)
            ->sendResponse();

        die(0);
    }

    public function deleteAction() {
        $requestParams = $this->_getAllParams();

        $response = $this->_model->delete($requestParams);

        // now that the job has been deleted, reroute the request by sending 303
        $href = Daiquiri_Config::getInstance()->getSiteUrl() .
                "/uws/" . urlencode($requestParams['moduleName']);

        $this->getResponse()
            ->clearHeaders()
            ->setHttpResponseCode(303)
            ->setHeader('Location', $href)
            ->sendResponse();

        die(0);
    }

    public function optionsAction() {
        $response = $this->_model->options();
        foreach ($response['headers'] as $key => $value) {
            $this->getResponse()->setHeader($key, $value);
        }
        $this->getResponse()->setBody(null);
    }
}