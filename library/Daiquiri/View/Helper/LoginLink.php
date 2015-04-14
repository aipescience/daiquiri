<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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
 * View helper class for showing the link to the Daiquiri login page if the user is not
 * yet logged in. Otherwise shows the logout link.
 *
 * @class   Daiquiri_View_Helper_LoginLink LoginLink.php
 */
class Daiquiri_View_Helper_LoginLink extends Zend_View_Helper_Abstract {

    /**
     * Zend's View object
     * @var Zend_View_Interface
     */
    public $view;

    /**
     * Setter for $view
     * @param Zend_View_Interface $view Zend's View object
     */
    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * Returns the login or logout link.
     * @return string $link
     */
    public function loginLink() {
        // get the auth object
        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $link = '<a href="' . $this->view->baseUrl('/auth/login/logout') . '">Logout</a>';
        } else {
            // user not logged in, display the login link
            $path = $this->view->path();
            if (strpos($path,'/core/layout') === false) {
                $link = '<a href="' . $this->view->baseUrl('/auth/login?redirect=' . $path) . '">Login</a>';
            } else {
                $link = '<a href="' . $this->view->baseUrl('/auth/login') . '">Login</a>';
            }
        }
        return $link;
    }

}
