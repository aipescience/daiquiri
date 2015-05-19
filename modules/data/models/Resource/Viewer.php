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

class Data_Model_Resource_Viewer extends Daiquiri_Model_Resource_Table {

    /**
     * Sets the adapter and the tablename of the resource retroactively.
     * @param string $database name of the database
     * @param string $table name of the table
     */
    public function init($database, $table = null) {
        // get the user adapter
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();

        // check if this database is the user datasbase
        if ($database === Daiquiri_Config::getInstance()->getUserDbName($username)) {
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        } else {
            // get the database id and check permission on database
            $databasesResource = new Data_Model_Resource_Databases();
            $result = $databasesResource->checkACL($database,'select');
            if ($result !== true) {
                throw new Daiquiri_Exception_NotFound();
            }

            // check permission on table access
            if ($table) {
                $tablesResource = new Data_Model_Resource_Tables();
                $result = $tablesResource->checkACL($database,$table,'select');
                if ($result !== true) {
                    throw new Daiquiri_Exception_NotFound();
                }
            }

            // if everything went ok get adapter
            $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($database);
        }

        // set adapter and table
        $this->setAdapter($adapter);
        if ($table) {
            $this->setTablename($table);
        }
    }

    /**
     * Fetches a pair of colums for plotting from the database table set init().
     * @param   string  $x      first column
     * @param   string  $y      second column
     * @param   int     $nrows  number of rows to return
     * @return  array   $rows
     */
    public function fetchPlot($x, $y, $nrows) {
        // get select object
        $select = $this->select(array('limit' => $nrows));
        $select->from($this->getTablename(), array($x,$y));

        // set fetch mode
        $this->getAdapter()->setFetchMode(Zend_Db::FETCH_NUM);

        // query database and return
        return $this->fetchAll($select);
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
            throw new Exception('Error with dump script permissions.');
        }

        $cmd = escapeshellcmd($adapter) . ' ' . escapeshellarg(implode('&', $args));

        exec($cmd);

        if (!file_exists($file)) {
            throw new Exception('Table dump could not be created.');
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
            throw new Exception('Error with dump script permissions.');
        }

        // fire up gearman and submit job
        $gearmanConf = Daiquiri_Config::getInstance()->query->download->gearman;

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
