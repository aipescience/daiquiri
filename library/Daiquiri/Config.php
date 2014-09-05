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
     * Daquiri configuration object, parsed from database
     * @var Zend_Config_Ini
     */
    protected $_config;

    /**
     * Zend configuration object, parsed from application.ini
     * @var Zend_Config_Ini
     */
    protected $_application;

    /**
     * Constructor. Empty.
     */
    public function __construct() {

    }

    public function setApplication($application = null) {
        if ($application === null) {
            $this->_application = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        } else {
            $this->_application = new Zend_Config($application, true);
        } 
    }

    public function setConfig($config = null) {
        if ($config === null) {
            // init the databases resource
            $resource = new Daiquiri_Model_Resource_Table();
            $resource->setTablename('Core_Config');

            $rows = $resource->fetchRows();

            if (empty($rows)) {
                return false;
            }
            
            $config = array();
            foreach ($rows as $row) {
                $keys = explode('.', $row['key']);
                $this->_buildConfig($config, $keys, $row['value']);
            }
        }

        $this->_config = new Zend_Config($config, true);

        return true;
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
        return $this->getConfig()->$key;
    }

    public function getConfig() {
        if (empty($this->_config)) {
            throw new Exception('Empty config');
        }
        return $this->_config;
    }

    public function getApplication() {
        if (empty($this->_application)) {
            throw new Exception('Empty application config');
        }
        return $this->_application;
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
        $prefix = $this->_config->query->userDb->prefix;
        $postfix = $this->_config->query->userDb->postfix;

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

    /**
     * Returns the download adapter for the query module in the right order.
     * @return array $output
     */
    public function getQueryDownloadAdapter() {

        $output = array();
        $input = Daiquiri_Config::getInstance()->query->download->adapter->toArray();

        // put the default adapter in front
        if (isset($input['default'])) {

            // sanity check
            if (! in_array($input['default'], $input['enabled'] )) {
                throw new Exception('Default adapter not in enbaled adapters.');
            }

            $output[] = array_merge(
                array('format' => $input['default']),
                $input['config'][$input['default']]
            );
        }

        foreach ($input['enabled'] as $key) {
            if (! isset($input['default']) || $key !== $input['default']) {
                $output[] = array_merge(
                    array('format' => $key),
                    $input['config'][$key]
                );
            }
        }

        return $output;
    }

}
