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
 * @class   Daiquiri_View_Helper_LoginStatus LoginStatus.php
 * @brief   Daiquiri View helper for displaying the identity of the user
 * 
 * View helper for displaying the identity of the user.
 *
 */
class Daiquiri_View_Helper_LoginStatus extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   loginStatus method - returns the identity (i.e. user name) of the user
     * @return  string
     * 
     * Returns the identity (i.e. user name) of the currently logged in user.
     *
     */
    public function loginStatus() {
        // get the auth object
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            // user is logged in
            $identity = $auth->getIdentity();
            return $identity;
        } else {
            return null;
        }
    }

}
