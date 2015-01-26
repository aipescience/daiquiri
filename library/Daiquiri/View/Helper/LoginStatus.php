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
