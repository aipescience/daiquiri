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
 * Abstract base class for all key value stores.
 */
class Daiquiri_Model_Resource_KeyValue extends Daiquiri_Model_Resource_Abstract {

    /**
     * The database table class connected to the model.
     * @var Daiquiri_Model_DbTable_Abstract
     */
    protected $_table;

    /**
     * Sets the database table class connected to the model.
     * @param string $tableclass
     */
    public function setTable($tableclass) {
        $this->_table = new $tableclass();
    }

    /**
     * Returns the database table class connected to the model.
     * @return Zend_Db_Table_Abstract
     */
    public function getTable() {
        return $this->_table;
    }

    protected function _fetchRow($key) {
        $select = $this->getTable()->select();
        $select->where('`key` = ?', $key);
        $row = $this->getTable()->fetchAll($select)->current();

        if ($row) {
            return $row;
        } else {
            return null;
        }
    }

    public function fetchRows() {
        $select = $this->getTable()->select();
        $rows = $this->getTable()->fetchAll($select);

        if ($rows) {
            return $rows->toArray();
        } else {
            return null;
        }
    }

    public function fetchRow($key) {
        $row = $this->_fetchRow($key);

        if ($row) {
            return $row->toArray();
        } else {
            return null;
        }
    }

    public function fetchValue($key) {
        $row = $this->_fetchRow($key);

        if ($row) {
            return $row->value;
        } else {
            return null;
        }
    }

    public function storeValue($key, $value) {
        $this->getTable()->insert(array(
            'key' => $key,
            'value' => $value
        ));
    }

    public function updateValue($key, $value) {
        $row = $this->_fetchRow($key);

        if ($row) {
            $row->value = $value;
            return $row->save();
        } else {
            throw new Exception('key "' . $key . '" not found');
        }
    }

    public function deleteValue($key) {
        $row = $this->_fetchRow($key);

        if ($row) {
            return $row->delete();
        } else {
            throw new Exception('key "' . $key . '" not found');
        }
    }

}
