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

class Data_Model_Viewer extends Daiquiri_Model_PaginatedTable {

    /**
     * Construtor. Sets resource.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Viewer');
    }

    /**
     * Returns the columns of a given table and database. 
     * @param string $database
     * @param string $table
     * @return array 
     */
    public function cols($db, $table, array $params = array()) {
        // set init table
        try {
            $this->getResource()->init($db, $table);
        } catch (Exception $e) {
            return array('status' => 'error', 'error' => $e->getMessage());
        }

        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
	    $colsIds = array_combine(array_keys($params['cols']),array_keys($params['cols']));
        } else {
	    // explode the input for the cols array
	    $params['cols'] = explode(',', $params['cols']);

	    // intersect the cols array with the cols in the database to get the ids of the cols
	    $colsIds = array_keys(array_intersect($this->getResource()->fetchCols(), $params['cols']));
        }

        // obtain column metadata (if this exists)
        $databaseModel = new Data_Model_Databases();
        $tableModel = new Data_Model_Tables();
        $dbId = $databaseModel->fetchIdWithName($db);

        if (!empty($dbId)) {
            $tableId = $tableModel->fetchIdWithName($dbId, $table);
            $tableData = $tableModel->getResource()->fetchRow($tableId);
        } else {
            // this table is not in the metadata table - let's see if we can get
            // further information from the table itself
            $describeResource = new Data_Model_Resource_Description();
            $tableData = $describeResource->describeTable($db, $table);
        }

        $params['colUcd'] = array();
        foreach ($colsIds as $id => $colsId) {
            $params['colUcd'][$id] = $tableData['columns'][$colsId]['ucd'];
        }

        // return columns ot this table
        $cols = array();
        foreach ($params['cols'] as $key => $name) {
            if ($name === 'row_id') {
                $cols[] = array(
                    'name' => $name,
                    'width' => '4em',
                    'sortable' => true,
                    'hidden' => true
                );
            } else if (strpos($params['colUcd'][$key], "meta.ref") !== false) {
                // is this a file daiquiri hosts or just a link?
                if (strpos($params['colUcd'][$key], "meta.file") !== false ||
                        strpos($params['colUcd'][$key], "meta.fits") !== false) {
                    // this is a file we host and can be downloaded
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    $cols[] = array(
                        'name' => $name,
                        'width' => '20em',
                        'sortable' => true,
                        'format' => array(
                            'type' => 'filelink',
                            'base' => $baseurl . '/files/index/single',
                        )
                    );
                } else if (strpos($params['colUcd'][$key], "meta.ref.uri") !== false) {
                    // this is just a link
                    $cols[] = array(
                        'name' => $name,
                        'width' => '20em',
                        'sortable' => true,
                        'format' => array(
                            'type' => 'link',
                            'target' => 'blank',
                        )
                    );
                } else if (strpos($params['colUcd'][$key], "meta.ref.ivorn") !== false) {
                    
                } else {
                    // we just show this as a link - meta.ref.url also ends up here
                    $cols[] = array(
                        'name' => $name,
                        'width' => '20em',
                        'sortable' => true,
                        'format' => array(
                            'type' => 'link',
                            'target' => 'blank',
                        )
                    );
                }
            } else {
                $col = array(
                    'name' => $name,
                    'width' => Daiquiri_Config::getInstance()->data->viewer->columnWidth,
                    'sortable' => true,
                );
                if (Daiquiri_Config::getInstance()->data->viewer->removeNewline) {
                    $col['format'] = array('removeNewline' => true);
                }
                $cols[] = $col;
            }
        }

        return array('status' => 'ok', 'cols' => $cols);
    }

    /**
     * Returns the rows of the given table and database.
     * @param string $database
     * @param string $table
     * @param array $params
     * @return array 
     */
    public function rows($db, $table, array $params = array()) {
        // set init table
        $this->getResource()->init($db, $table);
        
        // set default columns
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        //properly escape the column names to handle any strange special character
        //foreach($params['cols'] as &$currCol) {
        //    $currCol = "`" . trim($currCol, "`") . "`";
        //}
        
        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        
        $rows = $this->getResource()->fetchRows($sqloptions);
        return $this->_response($rows, $sqloptions);;
    }

}