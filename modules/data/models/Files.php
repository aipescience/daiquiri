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
            throw new Daiquiri_Exception_BadRequest();
        }

        // find file
        $file = $this->_findFile($name);
        if (empty($file)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // determine mime type of this file
        $finfo = new finfo;

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
            throw new Daiquiri_Exception_BadRequest();
        }

        // find file
        $files = $this->_findFile($name);

        if (count($files) > 1) {
            throw new Exception('More than one file found.');
        } else if (count($files) == 0) {
            throw new Daiquiri_Exception_NotFound();
        }

        $size = filesize($files[0]);

        return array('name' => $name, 'size' => $size, 'status' => 'ok');
    }

    /**
     * Sends all files from a column in a users table to the client.
     * @param string $name name of the database table
     * @param string $column name of the column
     */
    public function multi($table, $column) {
        // look for the files in the table
        $colFiles = $this->_getFilesInCol($table, $column);
        if (empty($colFiles)) {
            throw new Daiquiri_Exception_NotFound();
        } 

        // leave some time for the file to be transferred
        ini_set('max_execution_time', 3600);

        // setup zipped transfer
        $fileName = $table . "_" . $column . ".zip";
        $zip = new ZipStream($fileName);
        $comment = "All files in column " . $column . " of table " . $table . " downloaded on " . date('l jS \of F Y h:i:s A');
        $zip->setComment($comment);

        // look for the files in the file system and stream files
        foreach ($colFiles as $colFile) {
            // look for file
            $file = $this->_findFile($colFile);
            if (empty($file)) {
                continue;
            }

            // zip and stream
            $fhandle = fopen($file, "rb");
            $zip->addLargeFile($fhandle, $colFile);
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
        // look for the files in the table
        $colFiles = $this->_getFilesInCol($table, $column);
        if (empty($colFiles)) {
            throw new Daiquiri_Exception_NotFound();
        } 

        // look for the files in the file system and aggregate size
        $size = 0;
        foreach ($colFiles as $colFile) {
            // look for file
            $file = $this->_findFile($colFile);
            if (empty($file)) {
                continue;
            }

            $size += filesize($file);
        }

        return array('name' => $table . "_" . $column . ".zip", 'size' => $size, 'status' => 'ok');
    }

    /**
     * Sends all files from a set of rows of a users table to the client.
     * @param string $name name of the database table
     * @param string $rowIds ids of the rows
     */
    public function row($table, array $rowIds) {
        // look for the files in the table
        $rowFiles = $this->_getFilesInRow($table, $rowIds);
        if (empty($rowFiles)) {
            throw new Daiquiri_Exception_NotFound();
        } 

        // leave some time for the file to be transferred
        ini_set('max_execution_time', 3600);

        // setup zipped transfer
        $fileName = $table . ".zip";
        $zip = new ZipStream($fileName);
        $comment = "All files connected to rows " . implode(", ", $rowIds) . " of table " . $table . " downloaded on " . date('l jS \of F Y h:i:s A');
        $zip->setComment($comment);

        // look for the files in the file system and stream files
        foreach ($rowFiles as $rowFile) {
            // look for file
            $file = $this->_findFile($rowFile);
            if (empty($file)) {
                continue;
            }

            // zip and stream
            $fhandle = fopen($file, "rb");
            $zip->addLargeFile($fhandle, $rowFile);
            fclose($fhandle);
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
        // look for the files in the table
        $rowFiles = $this->_getFilesInRow($table, $rowIds);
        if (empty($rowFiles)) {
            throw new Daiquiri_Exception_NotFound();
        } 

        $size = 0;
        foreach ($rowFiles as $rowFile) {
            // look for file
            $file = $this->_findFile($rowFile);
            if (empty($file)) {
                continue;
            }

            $size += filesize($file);
        }

        return array('name' => $table . ".zip", 'size' => $size, 'status' => 'ok');
    }

    /**
     * Returns the absolute path of a specific file.
     * @param string $name name of the file
     * @return string $file
     */
    private function _findFile($name) {
        if (empty(Daiquiri_Config::getInstance()->data->files->static)) {
            throw new Exception('No static file directories defined.');
        }

        $directories = Daiquiri_Config::getInstance()->data->files->static->toArray();

        if (empty($directories)) {
            throw new Exception('No static file directories defined.');
        }

        if (empty($name)) {
            return false;
        }

        $files = array();
        foreach ($directories as $dir) {
            $files = array_merge($files, $this->_findFileRec($name, $dir));

        }

        if (count($files) > 1) { 
            throw new Exception('More than one file found.');
        } elseif (count($files) === 0) {
            return false;
        } else {
            return $files[0];
        }
    }

    /**
     * Recursive function to find a specific file.
     * @param string $name name of the file
     * @param string $currDir current directory
     * @return array $files
     */
    private function _findFileRec($name, $currDir) {
        $subdirs = glob($currDir . '/*', GLOB_ONLYDIR|GLOB_NOSORT);

        $files = array();
        foreach($subdirs as $subdir) {
            $files = array_merge($files, $this->_findFileRec($name, $subdir));
        }

        if(file_exists($currDir . DIRECTORY_SEPARATOR . $name)) {
                $files[] = $currDir . DIRECTORY_SEPARATOR . $name;
        }

        return $files;
    }

    /**
     * Returns all files of a specific column in a users table.
     * @param string $name name of the database table
     * @param string $column name of the column
     * @return array $files
     */
    private function _getFilesInCol($table, $column) {
        if (empty($table) || empty($column)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);
        $viewer = new Data_Model_Viewer();
        try {
            $response = $viewer->cols(array('db' => $db, 'table' => $table));
        } catch (Exception $e) {
            throw new Daiquiri_Exception_NotFound();
        }

        // extract file link columns by looking for the format type filelink ...
        $fileCols = array();
        foreach ($response['cols'] as $key => $col) {
            if (isset($col['format']['type']) && $col['format']['type'] === "filelink") {
                $fileCols[] = $col['name'];
            }
        }

        // get rows from the database
        $rows = $viewer->getResource()->fetchRows();

        // loop over rows, gather files, and return
        $files = array();
        foreach ($rows as $row) {
            foreach ($fileCols as $fileCol) {
                $files[] = $row[$fileCol];
            }
        }
        return $files;
    }

    /**
     * Returns all files of a set of rows in a users table.
     * @param string $name name of the database table
     * @param array $rowIds ids of the rows
     * @return array $files
     */
    private function _getFilesInRow($table, $rowIds) {
        if (empty($table) || empty($rowIds)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // get the column of the result set to obtain a list of all files we are dealing with
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $db = Daiquiri_Config::getInstance()->getUserDbName($username);
        $viewer = new Data_Model_Viewer();
        try {
            $response = $viewer->cols(array('db' => $db, 'table' => $table));
        } catch (Exception $e) {
            throw new Daiquiri_Exception_NotFound();
        }

        // extract file link columns by looking for the format type filelink ...
        $fileCols = array();
        foreach ($response['cols'] as $key => $col) {
            if (isset($col['format']['type']) && $col['format']['type'] === "filelink") {
                $fileCols[] = $col['name'];
            }
        }
        
        // escape input row ids
        $escapedIds = array();
        foreach ($rowIds as $rowId) {
            $escapedIds[] = (int) $rowId;
        }
        $sqloptions = array('orWhere' => array('row_id IN (' . implode(',',$escapedIds) .  ')'));

        // get rows from the database
        $rows = $viewer->getResource()->fetchRows($sqloptions);

        // loop over rows, gather files, and return
        $files = array();
        foreach ($rows as $row) {
            foreach ($fileCols as $fileCol) {
                $files[] = $row[$fileCol];
            }
        }
        return $files;
    }



}
