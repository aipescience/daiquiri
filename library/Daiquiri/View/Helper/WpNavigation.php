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
        if (Daiquiri_Config::getInstance()->cms->enabled) {
            $this->view->addScriptPath(Daiquiri_Config::getInstance()->cms->navPath);
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
