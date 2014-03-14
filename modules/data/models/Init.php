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

    public function parseOptions(array $options) {
        if (!isset($this->_input_options['data'])) {
            $input = array();
        } else if (!is_array($this->_input_options['data'])) {
            $this->_error('Data options need to be an array.');
        } else {
            $input = $this->_input_options['data'];
        }

        $output = $input;
        $options['data'] = $output;
        return $options;
    }

    public function init(array $options) {
        if ($options['config']['data']) {
            // get role model
            $authRoleModel = new Auth_Model_Roles();

            // create column entries in the data module
            if (isset($options['data']['columns']) &&
                    is_array($options['data']['columns'])) {
                $dataColumnsModel = new Data_Model_Columns();
                if ($dataColumnsModel->getResource()->countRows() == 0) {
                    foreach ($options['data']['columns'] as $a) {

                        $a['publication_role_id'] = $authRoleModel->getId($a['publication_role']);
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
            if (isset($options['data']['tables']) &&
                    is_array($options['data']['tables'])) {
                $dataTablesModel = new Data_Model_Tables();
                if ($dataTablesModel->getResource()->countRows() == 0) {
                    foreach ($options['data']['tables'] as $a) {
                        echo '    Generating metadata for table: ' . $a['name'] . PHP_EOL;

                        $a['publication_role_id'] = $authRoleModel->getId($a['publication_role']);
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
            if (isset($options['data']['databases'])
                    && is_array($options['data']['databases'])) {
                $dataDatabasesModel = new Data_Model_Databases();
                if ($dataDatabasesModel->getResource()->countRows() == 0) {
                    foreach ($options['data']['databases'] as $a) {
                        echo '    Generating metadata for database: ' . $a['name'] . PHP_EOL;

                        $a['publication_role_id'] = $authRoleModel->getId($a['publication_role']);
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
            if (isset($options['data']['functions']) &&
                    is_array($options['data']['functions'])) {
                $dataFunctionsModel = new Data_Model_Functions();
                if ($dataFunctionsModel->getResource()->countRows() == 0) {
                    foreach ($options['data']['functions'] as $a) {

                        $a['publication_role_id'] = $authRoleModel->getId($a['publication_role']);
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

}

