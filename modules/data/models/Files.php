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

require_once(Daiquiri_Config::getInstance()->core->libs->PHPZip . '/ZipStream.php');

class Data_Model_Files extends Daiquiri_Model_Abstract {

    /**
     * Returns a list of all available files.
     * @return array $response
     */
    public function index() {
        $directories = Daiquiri_Config::getInstance()->data->files->static->toArray();

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

        return array('status' => 'ok', 'files' => $files);
    }

    /**
     * Sends a single file to the client.
     * @param string $name name of the file
     */
    public function single($name) {
        if (empty($name)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // find file
        $files = $this->_findFile($name);

        if (count($files) > 1) {
            return array("status" => "err_multi");
        } else if (count($files) == 0) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // determine mime type of this file
        $finfo = new finfo;

        $file = $files[0];
        $mime = $finfo->file($file, FILEINFO_MIME);
        $fileName = basename($file);

        http_send_content_disposition($fileName, true);
        http_send_content_type($mime);
        http_send_file($file);
    }

    /**
     * Returns a the size of a single file.
     * @param string $name name of the file
     * @return array $response
     */
    public function singleSize($name) {
        if (empty($name)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // find file
        $files = $this->_findFile($name);

        if (count($files) > 1) {
            return array("status" => "err_multi");
        } else if (count($files) == 0) {
            throw new Daiquiri_Exception_Forbidden();
        }

        $size = filesize($files[0]);

        return array('size' => $size, 'status' => 'ok');
    }

    /**
     * Sends all files from a column in a users table to the client.
     * @param string $name name of the database table
     * @param string $column name of the column
     */
    public function multi($table, $column) {
        $rows = $this->_getFilesInCol($table, $column);

        // leave some time for the file to be transferred
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

    /**
     * Returns a the size of all files from a column in a users table.
     * @param string $name name of the database table
     * @param string $column name of the column
     * @return array $response
     */
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

    /**
     * Sends all files from a set of rows of a users table to the client.
     * @param string $name name of the database table
     * @param string $rowIds ids of the rows
     */
    public function row($table, array $rowIds) {
        $data = $this->_getFilesInRow($table, $rowIds);

        if ($data['rows'] === NULL) {
            return NULL;
        }

        // leave some time for the file to be transferred
        ini_set('max_execution_time', 3600);

        $fileName = $table . ".zip";

        $zip = new ZipStream($fileName);

        $comment = "All files connected to rows " . implode(", ", $rowIds) . " of table " . $table . " downloaded on " . date('l jS \of F Y h:i:s A');

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

    /**
     * Returns a the size of a set of rows of a users table to the client.
     * @param string $name name of the database table
     * @param string $rowIds ids of the rows
     * @return array $response
     */
    public function rowSize($table, array $rowIds) {
        $data = $this->_getFilesInRow($table, $rowIds);

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

    /**
     * Returns the absolute path of a specific file.
     * @param string $name name of the file
     * @return array $file
     */
    private function _findFile($name) {
        if (empty(Daiquiri_Config::getInstance()->data->files->static)) {
            return array();
        }

        $directories = Daiquiri_Config::getInstance()->data->files->static->toArray();

        if (empty($directories)) {
            return array();
        }

        $file = array();
        foreach ($directories as $dir) {
            $file = array_merge($file, $this->_findFileRec($name, $dir));

        }

        return $file;
    }

    /**
     * Recursive function to find a specific file.
     * @param string $name name of the file
     * @param string $currDir current directory
     * @return array $file
     */
    private function _findFileRec($name, $currDir) {
        $subdirs = glob($currDir . '/*', GLOB_ONLYDIR|GLOB_NOSORT);

        $file = array();
        foreach($subdirs as $subdir) {
            $file = array_merge($file, $this->_findFileRec($name, $subdir));
        }

        if(file_exists($currDir . DIRECTORY_SEPARATOR . $name)) {
                $file[] = $currDir . DIRECTORY_SEPARATOR . $name;
        }

        return $file;
    }

    /**
     * Returns all files of a specific column in a users table.
     * @param string $name name of the database table
     * @param string $column name of the column
     * @return array $rows
     */
    private function _getFilesInCol($table, $column) {
        if (empty($table) || empty($column)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        // get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);

        $viewer = new Data_Model_Viewer();
        try {
            $cols = $viewer->cols($db, $table);
        } catch (Exception $e) {
            throw new Daiquiri_Exception_Forbidden();
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

    /**
     * Returns all files of a set of rows in a users table.
     * @param string $name name of the database table
     * @param @param string $rowIds ids of the rows
     * @return array $data 
     */
    private function _getFilesInRow($table, array $rowIds) {
        if (empty($table) || empty($rowIds)) {
            throw new Daiquiri_Exception_Forbidden();
        }

        //get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);

        $viewer = new Data_Model_Viewer();
        try {
            $cols = $viewer->cols($db, $table);
        } catch (Exception $e) {
            throw new Daiquiri_Exception_Forbidden();
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

        foreach ($rowIds as $rowId) {
            try {
                $row = $viewer->getResource()->fetchRow($rowId);
            } catch (Exception $e) {
                return NULL;
            }
            $rows[] = $row;
        }

        return array('cols' => $cols['data'], 'rows' => $rows);
    }



}
