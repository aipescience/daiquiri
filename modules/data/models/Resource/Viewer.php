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

class Data_Model_Resource_Viewer extends Daiquiri_Model_Resource_Simple {

    /**
     * Sets the adapter and the tablename of the resource retroactively.
     * @param string $db name of the database
     * @param string $table name of the table
     */
    public function init($db, $table = null) {
        // get the user adapter
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();

        // check if this db is the user datasbase
        if ($db === Daiquiri_Config::getInstance()->getUserDbName($username)) {
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        } else {
            // get the database id and check permission on database
            $databasesResource = new Data_Model_Resource_Databases();
            $databaseId = $databasesResource->fetchId($db);
            if ($databaseId === false) {
                throw new Exception("Requested database not available");
            }

            $result = $databasesResource->checkACL($databaseId,'select');
            if ($result !== true) {
                throw new Exception("Requested database not available");
            }

            // check permission on table access
            if ($table) {
                $tablesResource = new Data_Model_Resource_Databases();
                $tableId = $tablesResource->fetchId($db, $table);
                if ($databaseId === false) {
                    throw new Exception("Requested table not available");
                }

                $result = $tablesResource->checkACL($tableId,'select');
                if ($result !== true) {
                    throw new Exception("Requested table not available");
                }
            }

            // if everything went ok get adapter
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($db);
        }

        // set adapter and table
        $this->setAdapter($adapter);
        if ($table) {
            $this->setTablename($table);
        }
    }

    /**
     * Returns the list of tables from the database adapter.
     * @return array $tables
     */
    public function fetchTables() {
        return $this->getAdapter()->listTables();
    }

    /**
     * Dumps the table in a given format on disk.
     * @param string $format
     * @param string $file
     */
    public function dumpTable($format, $file) {
        // get the adapter config
        $config = $this->getAdapter()->getConfig();

        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->adapter;
        $binPath = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->binPath;
        $compress = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->compress;
        $args = array(
            'binPath=' . $binPath,
            'dbname=' . $config['dbname'],
            'username=' . $config['username'],
            'password=' . $config['password'],
            'table=' . $this->getTablename(),
            'compress=' . $compress,
            'file=' . $file
        );
        if (isset($config['host']) && isset($config['port'])) {
            $args[] = 'host=' . $config['host'];
            $args[] = 'port=' . $config['port'];
        } elseif ($config['host'] === 'localhost') {
            $args[] = 'socket=' . Daiquiri_Config::getInstance()->core->system->mysql->socket;
        } else {
            throw new Exception('Error in database connection configuration.');
        }

        // check permissions on the dump script
        // (scripts need to be executable by every body...)
        $perm = fileperms($adapter);
        $refPerm = octdec(111);

        // check if execution bits are set
        if (($perm & $refPerm) !== $refPerm) {
            throw new Exception('Error with dump script permissions. Please set them correctly.');
        }

        $cmd = escapeshellcmd($adapter) . ' ' . escapeshellarg(implode('&', $args));

        exec($cmd);

        if (!file_exists($file)) {
            throw new Exception('Error: table dump could not be created.');
        }
    }

    /**
     * Dumps the table in a given format on disk using gearman.
     * @param string $format
     * @param string $file
     */
    public function dumpTableGearman($format, $file) {
        // get the adapter config
        $config = $this->getAdapter()->getConfig();

        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->adapter;
        $binPath = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->binPath;
        $compress = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->compress;

        $job = array("adapter" => $adapter,
            'binPath' => $binPath,
            "username" => $config['username'],
            "password" => $config['password'],
            "dbname" => $config['dbname'],
            "table" => $this->getTablename(),
            "file" => $file,
            "lockFile" => $file . ".lock",
            'compress' => $compress,
        );

        if (isset($config['host']) && isset($config['port'])) {
            $job['host'] = $config['host'];
            $job['port'] = $config['port'];
        } elseif ($config['host'] === 'localhost') {
            $job['socket'] = Daiquiri_Config::getInstance()->core->system->mysql->socket;
        } else {
            throw new Exception('Error in database connection configuration.');
        }

        // check permissions on the dump script
        // (scripts need to be executable by every body...)
        $perm = fileperms($adapter);
        $refPerm = octdec(111);

        // check if execution bits are set
        if (($perm & $refPerm) !== $refPerm) {
            throw new Exception('Error with dump script permissions. Please set them correctly.');
        }

        // fire up gearman and submit job
        $gearmanConf = Daiquiri_Config::getInstance()->query->download->queue->gearman;

        $gmclient = new GearmanClient();

        $gmclient->addServer($gearmanConf->host, $gearmanConf->port);

        $jobHandle = $gmclient->doBackground("dumpTableJob", json_encode($job));
    }


    /**
     * Streams the table in a given format to the user.
     * @param string $format
     * @param string $file
     */
    public function streamTable($format, $file, $script) {
        // at this stage, the HTML header is already set to download file - any errors thrown here
        // are written to the file and are not shown in the browser!

        // get the adapter config
        $config = $this->getAdapter()->getConfig();

        $binPath = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->binPath;
        $args = array(
            'binPath=' . $binPath,
            'dbname=' . $config['dbname'],
            'username=' . $config['username'],
            'password=' . $config['password'],
            'table=' . $this->getTablename(),
            'file=' . $file
        );
        if (isset($config['host']) && isset($config['port'])) {
            $args[] = 'host=' . $config['host'];
            $args[] = 'port=' . $config['port'];
        } elseif ($config['host'] === 'localhost') {
            $args[] = 'socket=' . Daiquiri_Config::getInstance()->core->system->mysql->socket;
        } else {
            throw new Exception('Error in database connection configuration.');
        }

        $cmd = escapeshellcmd($script) . ' ' . escapeshellarg(implode('&', $args));

        passthru($cmd);

        exit();
    }

}
