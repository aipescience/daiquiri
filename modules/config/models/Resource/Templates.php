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

/**
 * Provides methods for accessing the Messages database table
 */
class Config_Model_Resource_Templates extends Daiquiri_Model_Resource_Table {

    /**
     * Construtor. Sets DbTable.
     */
    public function __construct() {
        $this->setTable('Daiquiri_Model_DbTable_Simple');
        $this->getTable()->setName('Config_Templates');
    }

    public function fetchRow($template) {
        if ($template) {
            $select = $this->getTable()->getSelect();
            $select->where("`template` = ?", $template);

            // get the rowset and return
            $row = $this->getTable()->fetchAll($select)->current();
            if ($row) {
                return $row->toArray();
            } else {
                throw new Exception("Template '$template' does not exist.");
            }
        } else {
            throw new Exception('$template not provided in ' . __CLASS__);
        }
    }

    public function updateRow($template, array $data) {
        if ($template) {
            $select = $this->getTable()->getSelect();
            $select->where("`template` = ?", $template);

            // get the rowset and return
            $row = $this->getTable()->fetchAll($select)->current();
            if ($row) {
                // update row
                foreach ($this->fetchCols() as $col) {
                    if (array_key_exists($col, $data)) {
                        $row->$col = $data[$col];
                    }
                }

                // save row
                $row->save();
            } else {
                throw new Exception("Template '$template' does not exist.");
            }
        } else {
            throw new Exception('$template not provided in ' . __CLASS__);
        }
    }

    public function deleteRow($template) {
        if ($template) {
            $select = $this->getTable()->getSelect();
            $select->where("`template` = ?", $template);

            $row = $this->getTable()->fetchAll($select)->current();
            if ($row) {
                $row->delete();
            } else {
                throw new Exception("Template '$template' does not exist.");
            }
        } else {
            throw new Exception('$template not provided in ' . __CLASS__);
        }
    }

}