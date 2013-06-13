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
 * @class   Daiquiri_View_Helper_LoginLink LoginLink.php
 * @brief   Daiquiri View helper for displaying the login link.
 * 
 * View helper for showing the link to the Daiquiri login page if the user is not 
 * yet logged in. Otherwise show the logout link.
 *
 */
class Daiquiri_View_Helper_LoginLink extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   loginLink method - return link to login if user not yet logged in, otherwise logout
     * @return  HTML with link
     * 
     * Produces the link to the Daiquiri login page if the user is not 
     * yet logged in. Otherwise show the logout link.
     *
     */
    public function loginLink() {
        // get the auth object
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $link = '<a href="' . $this->view->baseUrl('/auth/login/logout') . '">Logout</a>';
        } else {
            // user not logged in, display the login link
            $link = '<a href="' . $this->view->baseUrl('/auth/login') . '">Login</a>';
        }
        return $link;
    }

}
