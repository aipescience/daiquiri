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

/**
 * Model for the currently running query jobs.
 */
class Files_Model_Files extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        
    }

    /**
     * Returns a list of all available files
     * @return array
     */
    public function index() {
        $directories = Daiquiri_Config::getInstance()->files->static->toArray();

        $filesTmp = array();
        foreach ($directories as $dir) {
            $filesTmp = array_merge($filesTmp, glob($dir . DIRECTORY_SEPARATOR . "*.*", GLOB_NOSORT));
        }

        $files = array();
        foreach ($filesTmp as $file) {
            $name = basename($file);
            $size = $this->_formatFileSize($file);
            $files[] = array("name" => $name, "size" => $size);
        }

        return $files;
    }

    public function single($name) {
        if (empty($name)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //first find file
        $files = $this->_findFile($name);

        if (count($files) > 1) {
            return array("status" => "err_multi");
        } else if (count($files) == 0) {
            throw new Daiquiri_Exception_AuthError();
        }

        //determine mime type of this file
        $finfo = new finfo;

        $file = $files[0];
        $mime = $finfo->file($file, FILEINFO_MIME);
        $fileName = basename($file);

        http_send_content_disposition($fileName, true);
        http_send_content_type($mime);
        http_send_file($file);
    }

    public function singleSize($name) {
        if (empty($name)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //first find file
        $files = $this->_findFile($name);

        if (count($files) > 1) {
            return array("status" => "err_multi");
        } else if (count($files) == 0) {
            throw new Daiquiri_Exception_AuthError();
        }

        $size = filesize($files[0]);

        return array('size' => $size, 'status' => 'ok');
    }

    public function multi($table, $column) {
        $rows = $this->_getFilesInCol($table, $column);

        //leave some time for the file to be transferred
        ini_set('max_execution_time', 3600);

        $fileName = $table . "_" . $column . ".zip";

        $zip = new ZipStream($fileName);

        $comment = "All files in column " . $column . " of table " . $table . " downloaded on " . date('l jS \of F Y h:i:s A');

        $zip->setComment($comment);

        foreach ($rows as $row) {
            //first find file
            if (!empty($row['cell'][1])) {
                $files = $this->_findFile($row['cell'][1]);
            } else {
                continue;
            }

            if (count($files) > 1) {
                continue;
            } else if (count($files) == 0) {
                continue;
            }

            $fhandle = fopen($files[0], "rb");
            $zip->addLargeFile($fhandle, $row['cell'][1]);
            fclose($fhandle);
        }

        $zip->finalize();
    }

    public function multiSize($table, $column) {
        $rows = $this->_getFilesInCol($table, $column);

        $size = 0;

        foreach ($rows as $row) {
            //first find file
            if (!empty($row['cell'][1])) {
                $files = $this->_findFile($row['cell'][1]);
            } else {
                continue;
            }

            if (count($files) > 1) {
                continue;
            } else if (count($files) == 0) {
                continue;
            }

            $size += filesize($files[0]);
        }

        return array('size' => $size, 'status' => 'ok');
    }

    public function row($table, array $row_ids) {
        $data = $this->_getFilesInRow($table, $row_ids);

        if ($data['rows'] === NULL) {
            return NULL;
        }

        // leave some time for the file to be transferred
        ini_set('max_execution_time', 3600);

        $fileName = $table . ".zip";

        $zip = new ZipStream($fileName);

        $comment = "All files connected to rows " . implode(", ", $row_ids) . " of table " . $table . " downloaded on " . date('l jS \of F Y h:i:s A');

        $zip->setComment($comment);

        foreach ($data['cols'] as $key => $col) {
            // first find file
            foreach ($data['rows'] as $row) {
                if (!empty($row[$col['name']])) {
                    $files = $this->_findFile($row[$col['name']]);
                } else {
                    continue;
                }

                if (count($files) > 1) {
                    continue;
                } else if (count($files) == 0) {
                    continue;
                }

                $fhandle = fopen($files[0], "rb");
                $zip->addLargeFile($fhandle, $row[$col['name']]);
                fclose($fhandle);
            }
        }

        $zip->finalize();
    }

    public function rowSize($table, array $row_ids) {
        $data = $this->_getFilesInRow($table, $row_ids);

        if ($data['rows'] === NULL) {
            return array('status' => 'error');
        }

        $size = 0;

        foreach ($data['cols'] as $key => $col) {
            // first find files
            foreach ($data['rows'] as $row) {
                if (!empty($row[$col['name']])) {
                    $files = $this->_findFile($row[$col['name']]);
                } else {
                    continue;
                }

                if (count($files) > 1) {
                    continue;
                } else if (count($files) == 0) {
                    continue;
                }

                $size += filesize($files[0]);
            }
        }

        return array('size' => $size, 'status' => 'ok');
    }

    private function _getFilesInCol($table, $column) {
        if (empty($table) || empty($column)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);

        $viewer = new Data_Model_Viewer();
        try {
            $cols = $viewer->cols($db, $table);
        } catch (Exception $e) {
            throw new Daiquiri_Exception_AuthError();
        }

        //extract file link columns by looking for the singleFileLink formatter...
        foreach ($cols as $key => $col) {
            if (isset($col['formatter']) && $col['formatter'] === "singleFileLink") {
                continue;
            } else {
                unset($cols[$key]);
            }
        }

        //get the data from the result table
        $params = array();
        $params['cols'] = array('row_id', $column);

        $rows = $viewer->rows($db, $table, $params)->rows;

        return $rows;
    }

    private function _getFilesInRow($table, array $row_ids) {
        if (empty($table) || empty($row_ids)) {
            throw new Daiquiri_Exception_AuthError();
        }

        //get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);

        $viewer = new Data_Model_Viewer();
        try {
            $cols = $viewer->cols($db, $table);
        } catch (Exception $e) {
            throw new Daiquiri_Exception_AuthError();
        }

        //extract file link columns by looking for the singleFileLink formatter...
        foreach ($cols['data'] as $key => $col) {
            if (isset($col['formatter']) && $col['formatter'] === "singleFileLink") {
                continue;
            } else {
                unset($cols['data'][$key]);
            }
        }

        //get the data from the result table
        $rows = array();

        foreach ($row_ids as $row_id) {
            try {
                $row = $viewer->getResource()->fetchRow($row_id);
            } catch (Exception $e) {
                return NULL;
            }
            $rows[] = $row;
        }

        return array('cols' => $cols['data'], 'rows' => $rows);
    }

    private function _findFile($name) {
        $directories = Daiquiri_Config::getInstance()->files->static->toArray();

        $file = array();
        foreach ($directories as $dir) {
            $file = array_merge($file, $this->_findFileRec($name, $dir));
        }

        return $file;
    }

    private function _findFileRec($name, $currDir) {
        $subdirs = glob($currDir . '/*', GLOB_ONLYDIR|GLOB_NOSORT);

        $file = array();
        foreach($subdirs as $subdir) {
            $file = array_merge($file, $this->_findFileRec($name, $subdir));
        }

        if(file_exists($dir . DIRECTORY_SEPARATOR . $name)) {
                $file[] = $dir . DIRECTORY_SEPARATOR . $name;
        }

        return $file;
    }

}
