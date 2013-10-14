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

        // get the columns and the corresponding ids from the database
        if (empty($params['cols'])) {
            $params['cols'] = $this->getResource()->fetchCols();
            $colsIds = array_combine(array_keys($params['cols']),array_keys($params['cols']));
        } else {
    	    // explode the input for the cols array
    	    $params['cols'] = explode(',', $params['cols']);

            $dbColsIds = array_flip($this->getResource()->fetchCols());
            $colsIds = array();
            foreach($params['cols'] as $key => $col) {
                $colsIds[$key] = $dbColsIds[$col];
            }
        }

        // obtain column metadata (if this exists)
        $databaseModel = new Data_Model_Databases();
        $tableModel = new Data_Model_Tables();
        $dbId = $databaseModel->fetchIdWithName($db);
       
        if (!empty($dbId)) {
            $tableId = $tableModel->fetchIdWithName($dbId, $table);
            $response = $tableModel->show($tableId);
            $tableData = $response['data'];
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
            $col = array(
                'id' => $colsIds[$key],
                'name' => $name,
                'sortable' => true
            );

            if ($name === 'row_id') {
                $col['width'] = '4em';
                $col['hidden'] = true;

            } else if (strpos($params['colUcd'][$key], "meta.ref") !== false) {
                // this is a link, it needs more space
                $col['width'] = '20em';

                // is this a file daiquiri hosts or just a link?
                if (strpos($params['colUcd'][$key], "meta.file") !== false ||
                        strpos($params['colUcd'][$key], "meta.fits") !== false) {
                    // this is a file we host and can be downloaded
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    $col['format'] = array(
                        'type' => 'filelink',
                        'base' => $baseurl . '/files/index/single',
                    );
                // TODO treat uri and ivorn different
                // } else if (strpos($params['colUcd'][$key], "meta.ref. i") !== false) {
                // } else if (strpos($params['colUcd'][$key], "meta.ref.ivorn") !== false) {
                } else {
                    // we just show this as a link - meta.ref.url also ends up here
                    $col['format'] = array(
                        'type' => 'link',
                        'target' => 'blank'
                    );
                }
            } else {
                // regular column, take the with from the config or a default one
                $width = Daiquiri_Config::getInstance()->data->viewer->columnWidth;
                if(empty($width)) {
                    $col['width'] = '12em';
                } else {
                    $col['width'] = $width;
                }

                // all removenewline flag if this is set in the config
                if (Daiquiri_Config::getInstance()->data->viewer->removeNewline) {
                    $col['format'] = array('removeNewline' => true);
                }
            }

            // append col to cols array
            $cols[] = $col;
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

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);
        $pk = array_values($this->getResource()->getTable()->getPrimary());
        $pk = $pk[0];
        return $this->_response($rows, $sqloptions, $pk);
    }

}