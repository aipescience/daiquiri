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
 * @class   Daiquiri_View_Helper_Redirector Redirector.php
 * @brief   Daiquiri View helper for issuing a redirect in a view
 * 
 * Daiquiri View helper for issuing a redirect in a view.
 *
 */
class Daiquiri_View_Helper_Redirector extends Zend_View_Helper_Abstract {

    /**
     * @brief   redirector method - redirects to given URL from within view
     * @param   $url: URL to redirect to
     * 
     * Issues a redirect to the given URL. Will terminate any further processing of
     * the view.
     */
    public function redirector($url) {
        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $helper->gotoUrl($url)->redirectAndExit();
    }

}
