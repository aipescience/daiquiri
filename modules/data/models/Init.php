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

class Data_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the data module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Data_Model_Viewer',
            'Data_Model_Files',
            'Data_Model_Functions',
            'Data_Model_Databases',
            'Data_Model_Tables',
            'Data_Model_Columns'
        );
    }

    /**
     * Returns the acl rules for the data module.
     * @return array $rules
     */
    public function getRules() {
        return array(
            'guest' => array(
                'Data_Model_Viewer' => array('rows','cols'),
                'Data_Model_Files' => array('index','single','singleSize','multi','multiSize','row','rowSize')
            ),
            'admin' => array(
                'Data_Model_Databases' => array('index','create','show','update','delete','export'),
                'Data_Model_Tables' => array('create','show','update','delete','export'),
                'Data_Model_Columns' => array('create','show','update','delete','export'),
                'Data_Model_Functions' => array('index','create','show','update','delete','export')
            )
        );
    }

    /**
     * Processes the 'data' part of $options['config'].
     */
    public function processConfig() {
        if (!isset($this->_init->input['config']['data'])) {
            $input = array();
        } else if (!is_array($this->_init->input['config']['data'])) {
            $this->_error('Auth config options needs to be an array.');
        } else {
            $input = $this->_init->input['config']['data'];
        }

        // create default entries
        $defaults = array(                
            'writeToDB' => 0,
            'viewer' => array(
                'removeNewline' => false,
                'columnWidth' => '12em'
            )
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // set options
        $this->_init->options['config']['data'] = $output;
    }

    /**
     * Processes the 'data' part of $options['init'].
     */
    public function processInit() {
        if (!isset($this->_init->input['init']['data'])) {
            $input = array();
        } else if (!is_array($this->_init->input['init']['data'])) {
            $this->_error('Data options needs to be an array.');
        } else {
            $input = $this->_init->input['init']['data'];
        }

        // just pass through
        $this->_init->options['init']['data'] = $input;
    }

    /**
     * Initializes the database with the init data for the data module.
     */
    public function init() {
        // create column entries in the data module
        if (isset($this->_init->options['init']['data']['columns']) 
            && is_array($this->_init->options['init']['data']['columns'])) {
            $dataColumnsModel = new Data_Model_Columns();
            if ($dataColumnsModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['init']['data']['columns'] as $a) {

                    $a['publication_role_id'] = Daiquiri_Auth::getInstance()->getRoleId($a['publication_role']);
                    unset($a['publication_role']);

                    try {
                        $r = $dataColumnsModel->create(null, $a);
                    } catch (Exception $e) {
                        $this->_error("Error in creating columns metadata:\n" . $e->getMessage());
                    }
                    $this->_check($r, $a);
                }
            }
        }

        // create table entries in the data module
        if (isset($this->_init->options['init']['data']['tables']) 
            && is_array($this->_init->options['init']['data']['tables'])) {
            $dataTablesModel = new Data_Model_Tables();
            if ($dataTablesModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['init']['data']['tables'] as $a) {
                    echo '    Generating metadata for table: ' . $a['name'] . PHP_EOL;

                    $a['publication_role_id'] = Daiquiri_Auth::getInstance()->getRoleId($a['publication_role']);
                    unset($a['publication_role']);

                    try {
                        $r = $dataTablesModel->create(null, $a);
                    } catch (Exception $e) {
                        $this->_error("Error in creating tables metadata:\n" . $e->getMessage());
                    }
                    $this->_check($r, $a);
                }
            }
        }

        // create database entries in the data module
        if (isset($this->_init->options['init']['data']['databases']) 
            && is_array($this->_init->options['init']['data']['databases'])) {
            $dataDatabasesModel = new Data_Model_Databases();
            if ($dataDatabasesModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['init']['data']['databases'] as $a) {
                    echo '    Generating metadata for database: ' . $a['name'] . PHP_EOL;

                    $a['publication_role_id'] = Daiquiri_Auth::getInstance()->getRoleId($a['publication_role']);
                    unset($a['publication_role']);

                    try {
                        $r = $dataDatabasesModel->create($a);
                    } catch (Exception $e) {
                        $this->_error("Error in creating database metadata:\n" . $e->getMessage());
                    }
                    $this->_check($r, $a);
                }
            }
        }

        // create function entries in the tables module
        if (isset($this->_init->options['init']['data']['functions']) 
            && is_array($this->_init->options['init']['data']['functions'])) {
            $dataFunctionsModel = new Data_Model_Functions();
            if ($dataFunctionsModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['init']['data']['functions'] as $a) {

                    $a['publication_role_id'] = Daiquiri_Auth::getInstance()->getRoleId($a['publication_role']);
                    unset($a['publication_role']);
                    
                    try {
                        $r = $dataFunctionsModel->create($a);
                    } catch (Exception $e) {
                        $this->_error("Error in creating function metadata:\n" . $e->getMessage());
                    }
                    $this->_check($r, $a);
                }
            }
        }
    }
}
