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
     * Returns the rows of the given table and database.
     * @param string $database
     * @param string $table
     * @param array $params
     * @return array 
     */
    public function rows($db, $table, array $params = array()) {
        // set init table
        $this->getResource()->init($db, $table);

        // get the primary key
        $pks = $this->getResource()->getTable()->getPrimary();
        $pk = $pks[0];

        // get the table from the resource
        $sqloptions = $this->_sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);
        $response = $this->_response($rows, $sqloptions, $pk);

        return $response;
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
        }

        // obtain column metadata (if this exists)
        $databaseModel = new Data_Model_Databases();
        $tableModel = new Data_Model_Tables();
        $dbId = $databaseModel->fetchIdWithName($db);

        if (!empty($dbId)) {
            $tableId = $tableModel->fetchIdWithName($dbId, $table);
            $tableData = $tableModel->getResource()->fetchRow($tableId);
        } else {
            //this table is not in the metadata table - let's see if we can get
            //further information from the table itself
            $describeResource = new Data_Model_Resource_Description();
            $tableData = $describeResource->describeTable($db, $table);
        }

        $params['colUcd'] = array();
        foreach ($params['cols'] as $key => $value) {
            $params['colUcd'][$key] = $tableData['columns'][$key]['ucd'];
        }

        // return columns ot this table
        $cols = array();
        foreach ($params['cols'] as $key => $name) {
            if ($name === 'row_id') {
                $cols[] = array(
                    'name' => $name,
                    'width' => '40em',
                    'sortable' => true,
                    'hidden' => true
                );
            } else if (strpos($params['colUcd'][$key], "meta.ref") !== false) {
                //is this a file daiquiri hosts or just a link?
                if (strpos($params['colUcd'][$key], "meta.file") !== false ||
                        strpos($params['colUcd'][$key], "meta.fits") !== false) {
                    //this is a file we host and can be downloaded
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    $cols[] = array(
                        'name' => $name,
                        'width' => '200em',
                        'sortable' => true,
                        'formatter' => 'singleFileLink',
                        'formatoptions' => array(
                            'baseLinkUrl' => $baseurl . '/files/index/single',
                        )
                    );
                } else if (strpos($params['colUcd'][$key], "meta.ref.uri") !== false) {
                    $cols[] = array(
                        'name' => $name,
                        'width' => '200em',
                        'sortable' => true,
                        'formatter' => 'link',
                        'formatoptions' => array(
                            'target' => 'blank',
                        )
                    );
                } else if (strpos($params['colUcd'][$key], "meta.ref.ivorn") !== false) {
                    
                } else {
                    //we just show this as a link - meta.ref.url also ends up here
                    $cols[] = array(
                        'name' => $name,
                        'width' => '200em',
                        'sortable' => true,
                        'formatter' => 'link',
                        'formatoptions' => array(
                            'target' => 'blank',
                        )
                    );
                }
            } else {
                $cols[] = array(
                    'name' => $name,
                    'width' => '80em',
                    'sortable' => true
                );
            }
        }

        return array('status' => 'ok', 'data' => $cols);
    }

}