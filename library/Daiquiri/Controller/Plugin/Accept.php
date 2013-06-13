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
 * @class   Daiquiri_Controller_Plugin_Accept Accept.php
 * @brief   Daiquiri Accept front controller plugin.
 * 
 * Class for the daiquiri front controller plugin handling application
 * requests or ajax calls.
 * 
 * If a request has the HTTP/1.1 header 'Accept' be set to 'application/json' or
 * to 'application/html', this plugin disables the rendering the layout and delivers
 * the view as html or the view variables as JSON. 
 */
class Daiquiri_Controller_Plugin_Accept extends Zend_Controller_Plugin_Abstract {

    /**
     * @brief   Value for HTTP/1.1 header 'Accept'
     * @var     string 
     */
    private $_header;

    /**
     * @brief   preDispatch method - called by Front Controller before dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * Checks whether HTTP/1.1 header contains 'Accept' and it is set to
     * 'application/json' or 'application/html'. Disables the layout. For JSON it 
     * disables also the redering of the view.
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        parent::preDispatch($request);

        // get the accept  headers
        $this->_header = $request->getHeader('Accept');

        if (in_array($this->_header, array('application/json', 'application/html'))) {
            // disable layout
            $layout = Zend_Controller_Action_HelperBroker::getExistingHelper('Layout');
            $layout->disableLayout();

            if ($this->_header === 'application/json') {
                $viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
                $viewRenderer->setNeverRender(true);
            }
        }
    }

    /**
     * @brief   postDispatch method - called by Front Controller after dispatch
     * @param   Zend_Controller_Request_Abstract $request: request object
     * 
     * Deliver JSON if 'applcation/json' is requested.
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        if ($this->_header === 'application/json') {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $view = $viewRenderer->view;

            $vars = Zend_Json::encode($view->getVars());
            $this->getResponse()->setBody($vars);
        }
    }

}
