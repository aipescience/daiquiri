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
 * @class   Daiquiri_Bootstrap Bootstrap.php
 * @brief   Daiquiri extensions to the Zend bootstrap process.
 * 
 * Adds all the front controller plugins and the session management capability
 * to the Zend bootstrap process.
 *
 */
class Daiquiri_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initFrontControllerPlugins() {
        $frontController = Zend_Controller_Front::getInstance();

        $frontController->registerPlugin(new Daiquiri_Controller_Plugin_Modules());
        $frontController->registerPlugin(new Daiquiri_Controller_Plugin_Config());

        $frontController->registerPlugin(new Daiquiri_Controller_Plugin_Authorization());
        $frontController->registerPlugin(new Daiquiri_Controller_Plugin_Accept());

        // exchange error helper
        $frontController->setParam('noErrorHandler', true);
        $frontController->registerPlugin(new Daiquiri_Controller_Plugin_ErrorHandler());
    }

    protected function _initConfig() {
        Daiquiri_Config::getInstance()->setApplication($this->getOptions());
    }

    /**
     * @brief   _initSession method - initialises session management
     * 
     * Initialises session management.
     */
    protected function _initSession() {
        // init session only when not called from the cli
        if (php_sapi_name() !== 'cli') {
            $dbAdapter = null;
            $dbResource = $this->getPluginResource('db');

            if ($dbResource !== null) {
                // we are using the regular db resource
                $this->bootstrap('db');
                $dbAdapter = $dbResource->getDbAdapter();
            } else {
                // we are using multidb
                $this->bootstrap('multidb');
                $multidbResource = $this->getPluginResource('multidb');
                $dbAdapter = $multidbResource->getDefaultDb();
            }

            // sanity checks
            $error = false;
            if ($dbAdapter === null) {
                $error = true;
            }
            try {
                if (count($dbAdapter->listTables()) == 0) {
                    $error = true;
                }
            } catch (Exception $e) {
                $error = true;
            }

            if ($error === true) {
                header('HTTP/1.1 503 Service Temporarily Unavailable',true,503);
                echo '<h1>The application is not correctly set up.</h1>';
                die();
            }

            $config = array(
                'name' => 'Auth_Sessions',
                'primary' => 'session',
                'modifiedColumn' => 'modified',
                'dataColumn' => 'data',
                'lifetimeColumn' => 'lifetime',
                'db' => $dbAdapter
            );

            Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config));
            Zend_Session::start();
        }
    }

}
