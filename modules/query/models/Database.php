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

require_once(Daiquiri_Config::getInstance()->core->libs->PHPZip . '/ZipStream.php');

class Query_Model_Database extends Daiquiri_Model_PaginatedTable {

    /**
     * @return array 
     */
    public function show() {
        // get current username and the user db
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $userDbName = Daiquiri_Config::getInstance()->getUserDbName($username);

        // prepare auto increment counters
        $table_id = 1;
        $column_id = 1;

        // prepate userdb array
        $userdb = array(
            'id' => 'userdb',
            'name' => $userDbName,
            'tables' => array()
        );

        // get tables of this database
        $resource = new Data_Model_Resource_Viewer();
        $resource->init($userdb['name']);
        $usertables = $resource->fetchTables();

        //find all the user tables that are currently open and cannot be queried for information
        // get the user adapter
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        $lockedTables = $adapter->query('SHOW OPEN TABLES IN `' . $userdb['name'] . '` WHERE In_use > 0')->fetchAll();

        foreach ($lockedTables as $table) {
            $key = array_search($table['Table'], $usertables);
            if ($key !== false) {
                unset($usertables[$key]);
            }
        }

        foreach ($usertables as $usertable) {
            $table = array(
                'id' => 'userdb-table-' . $table_id++,
                'name' => $usertable,
                'columns' => array()
            );

            try {
                $resource->init($userdb['name'], $usertable);
            } catch (Exception $e) {
                continue;
            }

            $usercolumns = $resource->fetchCols();
            foreach ($usercolumns as $usercolumn) {
                $table['columns'][] = array(
                    'id' => 'userdb-column-' . $column_id++,
                    'name' => $usercolumn
                );
            }

            $userdb['tables'][] = $table;
        }

        return $userdb;
    }

    public function download($table, array $formParams = array()) {

        if (empty($table)) {
            return array('status' => 'error', 'error' => 'Error: table not set');
        }

        // create the form object
        $formats = array();
        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->toArray();
        foreach ($adapter['enabled'] as $key) {
            $formats[$key] = $adapter['config'][$key]['name'];
        }
        $form = new Query_Form_Download(array(
                'formats' => $formats
            ));
        
        // init errors array
        $errors = array();

        if (!empty($formParams)) {
            // reinit csrf
            $csrf = $form->getCsrf();
            $csrf->initCsrfToken();

            if ($form->isValid($formParams)) {
                $response = array();

                // get the form values
                $values = $form->getValues();
                $format = $values['download_format'];

                // sanity check for format
                if (!in_array($format, Daiquiri_Config::getInstance()->query->download->adapter->enabled->toArray())) {
                    throw new Exception('Error: format not valid.');
                }

                // create link and file sysytem path for table dump
                $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
                $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;
                $filename = $table . $suffix;
                $url = '/query/index/file?table=' . $table . '&format=' . $format;
                $regenUrl = '/query/index/regen?table=' . $table . '&format=' . $format;
                $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
                $file = $dir . DIRECTORY_SEPARATOR . $filename;

                //get queue type and validate
                $queueType = strtolower(Daiquiri_Config::getInstance()->query->download->queue->type);
                if ($queueType !== "simple" and $queueType !== "gearman") {
                    throw new Exception('Download queue type not valid');
                }

                // create dir if neccessary
                if (!is_dir($dir)) {
                    if (mkdir($dir) === false) {
                        return array(
                            'status' => 'error', 
                            'errors' => array(
                                'form' => 'Configuration of download setup wrong'
                                ),
                            'form' => $form,
                            'csrf' => $csrf->getHash(), 
                        );
                    }

                    chmod($dir, 0775);
                }

                if (!file_exists($file) && ($queueType === "simple" || empty($queueType))) {
                    //get the user db name
                    $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
                    $db = Daiquiri_Config::getInstance()->getUserDbName($username);

                    // get the resource and create dump
                    $resource = new Data_Model_Resource_Viewer();
                    $resource->init($db, $table);
                    try {
                        $resource->dumpTable($format, $file);
                    } catch (Exception $e) {
                        return array(
                            'status' => 'error',
                            'errors' => array(
                                'form' => $e->getMessage()
                                ),
                            'form' => $form,
                            'csrf' => $csrf->getHash(), 
                        );
                    }
                }

                if ((!file_exists($file) || file_exists($file . ".lock")) && $queueType === "gearman") {
                    //check if GearmanManager is up and running
                    if (!file_exists(Daiquiri_Config::getInstance()->query->download->queue->gearman->pid)) {
                        //check if we have write access to actually create this PID file
                        if(!is_writable(dirname(Daiquiri_Config::getInstance()->query->download->queue->gearman->pid))) {
                            return array(
                                'status' => 'error',
                                'errors' => array(
                                    'form' => 'Cannot write to the gearman PID file location'
                                    ),
                                'form' => $form,
                                'csrf' => $csrf->getHash(), 
                                );
                        }

                        $gearmanConf = Daiquiri_Config::getInstance()->query->download->queue->gearman;

                        //not there, start GearmanManager
                        $cmd = escapeshellcmd($gearmanConf->manager) .
                                ' -d' .
                                ' -D ' . escapeshellcmd($gearmanConf->numThread) .
                                ' -h ' . escapeshellcmd($gearmanConf->host) . ':' . escapeshellcmd($gearmanConf->port) .
                                ' -P ' . escapeshellcmd($gearmanConf->pid) .
                                ' -w ' . escapeshellcmd($gearmanConf->workerDir) .
                                ' -r 1 > /tmp/Daiquiri_GearmanManager.log &';

                        shell_exec($cmd);
                        // DOES NOT WORK IN NEWER PHP, NEED TO BE FIXED 
                        // http://stackoverflow.com/questions/12322811/call-time-pass-by-reference-has-been-removed
                        //check if pid exists, if not, an error occured - wait for 10 seconds to start gearman manager
                        $count = 0;
                        while (!file_exists($gearmanConf->pid)) {
                            $count += 1;
                            sleep(1);

                            if ($count > 10) {
                                throw new Exception('Error: Could not start GearmanManager.');
                            }
                        }
                    }

                    //check if lockfile is present and if not, create
                    if (!file_exists($file . ".lock")) {
                        if (file_exists($file . ".err")) {
                            return array(
                                'status' => 'error',
                                'errors' => array(
                                    'form' => 'An error occured.'
                                    ),
                                'form' => $form,
                                'csrf' => $csrf->getHash(), 
                                );
                        }

                        //write lock file
                        touch($file . ".lock");

                        //get the user db name
                        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
                        $db = Daiquiri_Config::getInstance()->getUserDbName($username);

                        // get the resource and create dump
                        $resource = new Data_Model_Resource_Viewer();
                        $resource->init($db, $table);

                        try {
                            $resource->dumpTableGearman($format, $file);
                        } catch (Exception $e) {
                            unlink($file . ".lock");
                            return array(
                            'status' => 'error',
                            'errors' => array(
                                'form' => $e->getMessage()
                                ),
                            'form' => $form,
                            'csrf' => $csrf->getHash(), 
                            );
                        }

                        return array(
                            'status' => 'pending',
                            'form' => $form,
                            'csrf' => $csrf->getHash(), 
                        );
                    } else {
                        return array(
                            'status' => 'pending',
                            'form' => $form,
                            'csrf' => $csrf->getHash(), 
                        );
                    }
                }

                return array('status' => 'ok',
                    'link' => Daiquiri_Config::getInstance()->getSiteUrl() . $url,
                    'regenerateLink' => Daiquiri_Config::getInstance()->getSiteUrl() . $regenUrl,
                    'form' => $form,
                    'csrf' => $csrf->getHash(), 
                    );

            } else {
                return array(
                    'form' => $form,
                    'csrf' => $form->getCsrf()->getHash(), 
                    'errors' => $form->getMessages(),
                    'status' => 'error',
                    );
            }
        }

        return array(
            'form' => $form,
            'csrf' => $form->getCsrf()->getHash(), 
            'errors' => $errors,
            'status' => 'form',
        );
    }

    public function stream($table, $format) {
        if (empty($table)) {
            return array('status' => 'error', 'error' => 'Error: table not set');
        }

        if (empty($format)) {
            return array('status' => 'error', 'error' => 'Error: format not set');
        }

        // create link and file sysytem path for table dump
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        if (isset(Daiquiri_Config::getInstance()->query->download->adapter->config->$format)) {
            $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;

            //remove any additional stuff (like compression endings) from suffix
            $parts = explode(".", $suffix);
            $suffix = "." . $parts[1];
        } else {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        $filename = $table . $suffix;
        $url = '/query/index/file?table=' . $table . '&format=' . $format;
        $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
        $file = $dir . DIRECTORY_SEPARATOR . $filename;

        // create dir if neccessary
        if (!is_dir($dir)) {
            if (mkdir($dir) === false) {
                return array('status' => 'error', 'error' => 'Error: configuration of download setup wrong');
            }

            chmod($dir, 0775);
        }

        //check if we support this method
        $formats = array();
        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->toArray();
        $bin = $adapter['config'][$format]['adapter'];
        foreach ($adapter['enabled'] as $key) {
            $formats[$key] = $adapter['config'][$key]['name'];
        }

        if (!array_key_exists($format, $formats)) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        //construct the streming file name (which has to be $bin with _stream at the end)
        $scriptName = pathinfo($bin);

        if (empty($scriptName['dirname']) && empty($scriptName['filename'])) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        $newScriptName = $scriptName['dirname'] . DIRECTORY_SEPARATOR . $scriptName['filename'] . "_stream." .
                $scriptName['extension'];

        if (!file_exists($newScriptName)) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        //check permissions on the dump script
        //(scripts need to be executable by every body...)
        $perm = fileperms($newScriptName);
        $refPerm = octdec(111);

        //check if execution bits are set
        if (($perm & $refPerm) !== $refPerm) {
            return array('status' => 'error', 'error' => 'Error with stream script permissions. Please set them correctly.');
        }

        //get rid of all the Zend output stuff
        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);

        ob_end_clean();

        http_send_content_disposition($filename);
        http_send_content_type("application/octet-stream");

        //check if this file already exists...
        //if yes just stream
        if (file_exists($file)) {
            passthru("cat " . escapeshellarg($file));
            exit();
        }

        if (!file_exists($file)) {
            //get the user db name
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $db = Daiquiri_Config::getInstance()->getUserDbName($username);

            // get the resource and create dump
            $resource = new Data_Model_Resource_Viewer();
            $resource->init($db, $table);
            $resource->streamTable($format, $file, $newScriptName);
        }

        return array('status' => 'ok');
    }

    public function file($table, $format) {
        if (empty($format) || empty($table)) {
            throw new Daiquiri_Exception_AuthError();
        }

        // create link and file sysytem path for table dump
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;
        $compress = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->compress;
        $filename = $table . $suffix;
        $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
        $file = $dir . DIRECTORY_SEPARATOR . $filename;

        //security
        if (!is_dir($dir)) {
            throw new Daiquiri_Exception_AuthError();
        }
        if (!file_exists($file)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //determine mime type of this file
        $finfo = new finfo;

        $mime = $finfo->file($file, FILEINFO_MIME);

        return array('file' => $file, 'filename' => $filename, 'mime' => $mime, 'compress' => $compress, 'status' => 'ok');
    }

    public function regen($table, $format) {
        if (empty($format) || empty($table)) {
            throw new Daiquiri_Exception_AuthError();
        }

        // create link and file sysytem path for table dump
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;
        $compress = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->compress;
        $filename = $table . $suffix;
        $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
        $file = $dir . DIRECTORY_SEPARATOR . $filename;

        //security
        if (!is_dir($dir)) {
            throw new Daiquiri_Exception_AuthError();
        }
        if (file_exists($file . ".lock")) {
            throw new Daiquiri_Exception_AuthError();
        }

        //delete the files...
        if (file_exists($file)) {
            unlink($file);
        }

        if (file_exists($file . ".err")) {
            unlink($file . ".err");
        }

        //now resubmit the download request
        return $this->download($table, array("format" => $format, "table" => $table));
    }

}