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
