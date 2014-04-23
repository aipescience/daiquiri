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
 * @class   Daiquiri_Config Config.php
 * @brief   Daiquiri config singleton storing all global configurations
 * 
 * This globally accessible singleton stores all the information about the
 * Daiquiri applications configuration. It is read from the database and also
 * incorporates the Zend configuration stuff.
 *
 */
class Daiquiri_Config extends Daiquiri_Model_Singleton {

    /**
     * Daquiri configuration object, parsed from database and application.ini
     * @var Zend_Config_Ini
     */
    protected $_daiquiri;

    /**
     * Zend configuration object, parsed from application.ini
     * @var Zend_Config_Ini
     */
    protected $_application;

    /**
     * Constructor. Load the configuration form the ini file into memory
     */
    protected function __construct() {
        $this->init();
    }

    public function init() {
        // init the databases resource
        try {
            $resource = new Daiquiri_Model_Resource_Table();
            $resource->setTablename('Config_Entries');

            $config = array();
            foreach ($resource->fetchRows() as $row) {
                $keys = explode('.', $row['key']);
                $this->_buildConfig($config, $keys, $row['value']);
            }
        } catch (Zend_Db_Table_Exception $e) {
            $config = array();
        }

        // init the database config object
        $this->_daiquiri = new Zend_Config($config, true);

        // init the daiquiri.ini file config object
        $this->_application = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        if ($this->_application->daiquiri !== null) {
            $this->_daiquiri->merge($this->_application->daiquiri);
        }
    }

    /**
     * Recursive function to build the config array.
     * @param array $a
     * @param array $keys
     * @param string $value
     */
    private function _buildConfig(array &$config, array &$keys, $value) {
        $key = array_shift($keys);
        if (empty($keys)) {
            $config[$key] = $value;
        } else {
            if (!array_key_exists($key, $config)) {
                $config[$key] = array();
            }
            if (is_array($config[$key])) {
                $this->_buildConfig($config[$key], $keys, $value);
            } else {
                throw new Exception('Bad config array: ' . print_r($key, true));
            }
        }
    }

    /**
     * Magic method to avoid calling getConfig().
     * @param string $key
     * @return Zend_Config_Ini
     */
    public function __get($key) {
        return $this->_daiquiri->$key;
    }

    public function getConfig() {
        return $this->_daiquiri->toArray();
    }


    public function isEmpty() {
        return ($this->_daiquiri->count() == 0);
    }

    /**
     * Returns the site url
     * @return string 
     */
    public function getSiteUrl() {
        return $this->getHost() . Zend_Controller_Front::getInstance()->getBaseUrl();
        ;
    }

    /** Returns the host url (without base url)
     * @return string
     */
    public function getHost() {
        $front = Zend_Controller_Front::getInstance();
        $server = $front->getRequest()->getServer();

        if (isset($server['HTTPS'])) {
            $host = 'https://';
        } else {
            $host = 'http://';
        }
        $host .= $server["SERVER_NAME"];
        if ($server["SERVER_PORT"] != "80") {
            $host .= ':' . $server["SERVER_PORT"];
        }

        return $host;
    }

    /** Returns the base url
     * @return string
     */
    public function getBaseUrl() {
        $front = Zend_Controller_Front::getInstance();
        return $front->getBaseUrl();
    }

    /**
     * Returns the web (default) database adapter
     * @return Zend_
     */
    public function getWebAdapter() {
        return Zend_Db_Table::getDefaultAdapter();
    }

    /**
     * Returns the name of the database, where a given user has full access to (MyDBs)
     * @param string $userName
     * @return string
     */
    public function getUserDbName($username) {
        $prefix = $this->_daiquiri->query->userDb->prefix;
        $postfix = $this->_daiquiri->query->userDb->postfix;

        return $prefix . $username . $postfix;
    }

    /**
     * Returns the user database adapter
     * @return Zend_
     */
    public function getUserDbAdapter($db = null, $username = null) {
        // get adapter configuration
        $config = $this->_application->resources->multidb->user->toArray();

        $adapter = $config['adapter'];
        unset($config['adapter']);

        if ($db === null) {
            if ($username === null) {
                $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            }
            $config['dbname'] = $this->getUserDbName($username);
        } else {
            $config['dbname'] = $db;
        }

        // turn off buffered query if using PDO_Mysql
        if ($adapter == "Pdo_Mysql") {
            $pdoParams = array(
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
            );

            $config['driver_options'] = $pdoParams;
        }

        // construct adapter and return
        return Zend_Db::factory($adapter, $config);
    }

    /**
     * Returns the configuration of the user database adapter
     * @param string $userName
     * @return Zend_
     */
    public function getUserDbAdapterConfig() {
        // get adapter configuration
        return $this->_application->resources->multidb->user->toArray();
    }

}
