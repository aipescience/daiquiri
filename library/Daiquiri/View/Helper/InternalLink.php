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

/**
 * @class   Daiquiri_View_Helper_InternalLink InternalLink.php
 * @brief   Daiquiri View helper for displaying links to Daiquiri related resources.
 * 
 * View helper for showing links to internal resources. Checks if ACL is positive otherwise
 * no link is generated.
 *
 */
class Daiquiri_View_Helper_InternalLink extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   internalLink method - returns a link to a Daiquiri related resource if ACL is positive
     * @param   array $options: containing keys: text, href, resource, permission, prepend, append
     * @return  HTML with link
     * 
     * Produces a link to a daiquiri internal resource. Checks if ACL are positive for the user, if not
     * no link will be produced and remains empty. The link is configured through the $option array with the
     * following parameters:
     *      - <b>text</b>: Text shown as link
     *      - <b>href</b>: Daiquiri internal link (relative to base url)
     *      - <b>resource</b>: The resource corresponding to the link
     *      - <b>prepend</b>: Any HTML that should be prepended to the link
     *      - <b>append</b>: Any HTML that should be appended to the link.
     */
    public function internalLink(array $options) {
        // check permissions
        if (array_key_exists('resource', $options) &&
                array_key_exists('permission', $options)) {
            if (!Daiquiri_Auth::getInstance()->checkAcl($options['resource'], $options['permission'])) {
                return '';
            }
        }

        $html = '';

        // prepend stuff
        if (array_key_exists('prepend', $options)) {
            $html .= $options['prepend'];
        }

        $html .= "<a href=\"{$this->view->baseUrl($options['href'])}\"";

        foreach($options as $key => $value) {
            if (!in_array($key, array('resource','permission','prepend','append','text','href'))) {
                $html .= " {$key}=\"{$value}\"";
            }
        }

        $html .= '>';

        // prepend stuff
        if (array_key_exists('text', $options)) {
            $html .= $options['text'];
        } else {
            $html .= $options['href'];
        }

        $html .= '</a>' ;

        // append stuff
        if (array_key_exists('append', $options)) {
            $html .= $options['append'];
        }

        return $html;
    }

}

