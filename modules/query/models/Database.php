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

require_once(Daiquiri_Config::getInstance()->core->libs->PHPZip . '/ZipStream.php');

class Query_Model_Database extends Daiquiri_Model_Abstract {

    /**
     * Creates the file to download.
     * @param array $formParams
     * @return array $response
     */
    public function download(array $formParams = array()) {
        // create the form object
        $form = new Query_Form_Download(array(
            'adapter' => Daiquiri_Config::getInstance()->getQueryDownloadAdapter()
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $values = $form->getValues();
                $response = $this->_createDownloadFile($values['download_tablename'],$values['download_format']);
                if ($response['status'] == 'error') {
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form,$response['error']);
                } else {
                    return $response;
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Regenerates the file to download.
     * @param array $formParams
     * @return array $response
     */
    public function regenerate(array $formParams = array()) {
        // create the form object
        $form = new Query_Form_Download(array(
            'adapter' => Daiquiri_Config::getInstance()->getQueryDownloadAdapter()
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                $values = $form->getValues();
                return $this->_createDownloadFile($values['download_tablename'],$values['download_format'], true);
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Streams the file to the client.
     * @param string $table table in the users database
     * @param string $format format for the download
     * @return array $response
     */
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
        } else {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        $filename = $this->_generateFileName($table, $suffix);
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

        // check if we support this method
        $formats = array();
        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->toArray();
        $bin = $adapter['config'][$format]['adapter'];
        foreach ($adapter['enabled'] as $key) {
            $formats[$key] = $adapter['config'][$key]['name'];
        }

        if (!array_key_exists($format, $formats)) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        // construct the streming file name (which has to be $bin with _stream at the end)
        $scriptName = pathinfo($bin);

        if (empty($scriptName['dirname']) && empty($scriptName['filename'])) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        $newScriptName = $scriptName['dirname'] . DIRECTORY_SEPARATOR . $scriptName['filename'] . "_stream." .
                $scriptName['extension'];

        if (!file_exists($newScriptName)) {
            return array('status' => 'error', 'error' => 'Error: format not supported by stream');
        }

        // check permissions on the dump script
        // (scripts need to be executable by every body...)
        $perm = fileperms($newScriptName);
        $refPerm = octdec(111);

        // check if execution bits are set
        if (($perm & $refPerm) !== $refPerm) {
            return array('status' => 'error', 'error' => 'Error with stream script permissions. Please set them correctly.');
        }

        // get rid of all the Zend output stuff
        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // set the right headers for stream
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // check if this file already exis
        // ts, if yes just stream
        if (file_exists($file)) {
            header('X-Sendfile: ' . $file);
        } else {
            //get the user db name
            $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
            $db = Daiquiri_Config::getInstance()->getUserDbName($username);

            // get the resource and create dump
            $resource = new Data_Model_Resource_Viewer();
            $resource->init($db, $table);
            $resource->streamTable($format, $file, $newScriptName); // this funtion will exit()
        }

        return array('status' => 'ok');
    }

    /**
     * Sends the file to the client.
     * @param string $table table in the users database
     * @param string $format format for the download
     */
    public function file($table, $format) {
        if (empty($format) || empty($table)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // create link and file sysytem path for table dump
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;
        $compress = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->compress;
        $filename = $this->_generateFileName($table, $suffix);
        $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
        $file = $dir . DIRECTORY_SEPARATOR . $filename;

        // security
        if (!is_dir($dir)) {
            throw new Daiquiri_Exception_Forbidden();
        }
        if (!file_exists($file)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // determine mime type of this file
        $finfo = new finfo;

        $mime = $finfo->file($file, FILEINFO_MIME);

        // get rid of all the Zend output stuff
        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);
        ob_end_clean();

        // set the right headers for file download
        header('X-Sendfile: ' . $file);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        return array('status' => 'ok');
    }

    /**
     * Returns the filename for a given table and suffix.
     * @param string $table table in the users database
     * @param string $format format for the download
     * @return string $filename
     */
    private function _generateFileName($table, $suffix) {
        //function generating the file name and escaping dangerous characters
        if ($suffix[0] != '.') {
            $suffix = '.' . $suffix;
        }

        //escaping the table
        $table = str_replace(array('/', '\\', '?', '%', '*', ':', '|', '"', '<', '>', ' '), "_", $table);

        return $table . $suffix;
    }

    /**
     * Creates a downloadable file from the given table of the users database
     * @param string $table table in the users database
     * @param string $suffix
     * @return array $response
     */
    private function _createDownloadFile($table, $format, $regen = false) {
        // sanity check for format
        if (!in_array($format, Daiquiri_Config::getInstance()->query->download->adapter->enabled->toArray())) {
            throw new Exception('Error: format not valid.');
        }

        // create link and file sysytem path for table dump
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $suffix = Daiquiri_Config::getInstance()->query->download->adapter->config->$format->suffix;
        $filename = $this->_generateFileName($table, $suffix);
        $url = '/query/download/file?table=' . $table . '&format=' . $format;
        $dir = Daiquiri_Config::getInstance()->query->download->dir . DIRECTORY_SEPARATOR . $username;
        $file = $dir . DIRECTORY_SEPARATOR . $filename;

        // get queue type and validate
        $queueType = strtolower(Daiquiri_Config::getInstance()->query->download->type);
        if ($queueType !== "direct" and $queueType !== "gearman") {
            throw new Exception('Download queue type not valid');
        }

        // create dir if neccessary
        if (!is_dir($dir)) {
            if (mkdir($dir) === false) {
                return array(
                    'status' => 'error',
                    'error' => 'Configuration of download directory wrong, please contact support.'
                );
            }

            chmod($dir, 0775);
        }

        // delete the old file if regen is set
        if ($regen === true) {
            if (file_exists($file . ".lock")) {
                throw new Daiquiri_Exception_Forbidden();
            }

            // delete the files...
            if (file_exists($file)) {
                unlink($file);
            }

            if (file_exists($file . ".err")) {
                unlink($file . ".err");
            }
        }

        if (!file_exists($file) && ($queueType === "direct" || empty($queueType))) {
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
                    'error' => $e->getMessage() . ' Please contact support.'
                );
            }
        }

        if ((!file_exists($file) || file_exists($file . ".lock")) && $queueType === "gearman") {
            // check if gearman is up and running
            exec('pgrep gearmand', $output, $return);
            if ($return != 0) {
                throw new Exception('gearmand is not running.');
            }

            // check if
            $restartGeamanManager = false;
            $pidfile = Daiquiri_Config::getInstance()->query->download->gearman->pid;
            if (file_exists($pidfile)) {
                $pid = file_get_contents($pidfile);
                exec('ps -p ' . $pid, $output, $return);
                if ($return != 0) {
                    $restartGeamanManager = true;
                }
            } else {
                $restartGeamanManager = true;
            }

            if ($restartGeamanManager) {
                // check if we have write access to actually create this PID file
                if(!is_writable(dirname(Daiquiri_Config::getInstance()->query->download->gearman->pid))) {
                    return array(
                        'status' => 'error',
                        'error' => 'Cannot write to the gearman PID file, please contact support.'
                    );
                }

                $gearmanConf = Daiquiri_Config::getInstance()->query->download->gearman;

                // not there, start GearmanManager
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
                // check if pid exists, if not, an error occured - wait for 10 seconds to start gearman manager
                $count = 0;
                while (!file_exists($gearmanConf->pid)) {
                    $count += 1;
                    sleep(1);

                    if ($count > 10) {
                        throw new Exception('Error: Could not start GearmanManager.');
                    }
                }
            }

            // check if lockfile is present and if not, create
            if (!file_exists($file . ".lock")) {
                if (file_exists($file . ".err")) {
                    return array(
                        'status' => 'error',
                        'error' => 'An error file exists on the server, please contact support.'
                    );
                }

                // write lock file
                touch($file . ".lock");

                // get the user db name
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
                        'error' => $e->getMessage() . ' Please contact support.'
                    );
                }

                return array('status' => 'pending');
            } else {
                return array('status' => 'pending');
            }
        }

        return array(
            'status' => 'ok',
            'link' => Daiquiri_Config::getInstance()->getSiteUrl() . $url,
            'format' => $format
        );
    }
}