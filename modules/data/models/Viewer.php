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

class Data_Model_Viewer extends Daiquiri_Model_Table {

    /**
     * Construtor. Sets resource and columns.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Viewer');
    }

    /**
     * Returns the columns of a given table and database. 
     * @param array $params get params of the request
     * @return array 
     */
    public function cols(array $params = array()) {
        // get db and table from params
        if (empty($params['db']) || empty($params['table'])) {
            return array('status' => 'error');
        } else {
            $db = $params['db'];
            $table = $params['table'];
        }

        // init table
        $this->getResource()->init($params['db'], $params['table']);

        // get columns from params or from the database
        if (empty($params['cols'])) {
            $params['cols'] = array_keys($this->getResource()->fetchCols());
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        // obtain table metadata
        $tablesResource = new Data_Model_Resource_Tables();
        $tableId = $tablesResource->fetchIdByName($db,$table);
        if ($tableId === false) {
            // this table is not in the metadata table - let's see if we can get
            // further information from the table itself
            $descResource = new Data_Model_Resource_Description();
            $descResource->init($params['db']);
            $tableMeta = $descResource->describeTable($params['table']);
        } else {
            // get the metadata from the metadata tables
            $tableMeta =  $tablesResource->fetchRow($tableId, true);
        } 

        // construct metadata array
        $meta = array();
        foreach ($tableMeta['columns'] as $key => $colMeta) {
            $meta[$colMeta['name']] = array(
                'id' => $key,
                'ucd' => explode(';',$colMeta['ucd'])
            );
        }

        // check if all colums are in the database
        if (count(array_intersect($params['cols'],array_keys($meta))) != count($params['cols'])) {
            throw new Exception('Some Columns are not in the database table');
        }

        // return columns ot this table
        $cols = array();
        foreach ($params['cols'] as $colname) {
            $col = array(
                'id' => $meta[$colname]['id'],
                'name' => $colname,
                'sortable' => true
            );

            if ($colname === 'row_id') {
                $col['width'] = '4em';
                $col['hidden'] = true;

            } else if (in_array('meta.ref', $meta[$colname]['ucd'])) {
                // this is a link, it needs more space
                $col['width'] = '20em';

                // is this a file daiquiri hosts or just a link?
                if (in_array('meta.file', $meta[$colname]['ucd']) || in_array('meta.fits', $meta[$colname]['ucd'])) {
                    // this is a file we host and can be downloaded
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    $col['format'] = array(
                        'type' => 'filelink',
                        'base' => $baseurl . '/files/index/single',
                    );
                } else {
                    // we just show this as a link - meta.ref.url also ends up here
                    $col['format'] = array(
                        'type' => 'link',
                        'target' => 'blank'
                    );
                }
                // TODO treat uri and ivorn different
            } else {
                // regular column, take the with from the config or a default one
                $width = Daiquiri_Config::getInstance()->data->viewer->columnWidth;
                if (empty($width)) {
                    $col['width'] = '12em';
                } else {
                    $col['width'] = $width;
                }

                // add removenewline flag if this is set in the config
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
     * @param array $params get params of the request
     * @return array 
     */
    public function rows(array $params = array()) {
        // get db and table from params
        if (empty($params['db']) || empty($params['table'])) {
            return array('status' => 'error');
        } else {
            $db = $params['db'];
            $table = $params['table'];
        }

        // set init table
        $this->getResource()->init($db, $table);

        // get columns from params or from the database
        if (empty($params['cols'])) {
            $params['cols'] = array_keys($this->getResource()->fetchCols());
        } else {
            $params['cols'] = explode(',', $params['cols']);
        }

        // get the table from the resource
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);
        $pk = $this->getResource()->fetchPrimary();
        return $this->getModelHelper('pagination')->response($rows,$sqloptions,$pk);
    }

}