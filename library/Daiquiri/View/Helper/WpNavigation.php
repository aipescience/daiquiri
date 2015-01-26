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
 * @class   Daiquiri_View_Helper_Cols Cols.php
 * @brief   Daiquiri View helper for displaying key value paired information
 * 
 * Class implementing a Zend view helper for displaying key value paired information
 * as a table where the keys are output in bold face and the value besides it as columns:
 * *key*: value 
 * 
 */
class Daiquiri_View_Helper_WpNavigation extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   wpNavigation method - produces a navigation list from the html files written by wordpress
     * @param   string $meny: name of the menu in wordpress
     * @return  HTML string
     * 
     * Produces a a navigation list from the html files written by the daiquiri wordpress plugin.
     * Used to make the wordpress navigation menus available in daiquiri.
     * 
     */
    public function wpNavigation($menu) {
        if (Daiquiri_Config::getInstance()->core->cms->enabled) {
            $this->view->addScriptPath(Daiquiri_Config::getInstance()->core->cms->navPath);
            try {
                return $this->view->partial($menu . '.html');
            } catch (Zend_View_Exception $e) {
                return '';
            }
        } else {
            return '';
        }
    }

}
