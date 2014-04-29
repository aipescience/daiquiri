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
 * @class   Daiquiri_Bootstrap Bootstrap.php
 * @brief   Daiquiri extensions to the Zend bootstrap process.
 * 
 * Adds all the front controller plugins and the session management capability
 * to the Zend bootstrap process.
 *
 */
class Daiquiri_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initFrontControllerPlugins() {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Daiquiri_Controller_Plugin_Modules());
        $front->registerPlugin(new Daiquiri_Controller_Plugin_Config());
        $front->registerPlugin(new Daiquiri_Controller_Plugin_ErrorHandler());
        $front->registerPlugin(new Daiquiri_Controller_Plugin_Authorization());
        $front->registerPlugin(new Daiquiri_Controller_Plugin_Accept());
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
