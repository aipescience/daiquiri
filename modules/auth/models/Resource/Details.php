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
 * Abstract base class for all reosurce models in the daiquiri framework
 */
class Auth_Model_Resource_Details extends Daiquiri_Model_Resource_KeyValue {

    /**
     * Constructor. Sets database table object.
     */
    public function __construct() {
        $this->setTable('Auth_Model_DbTable_Details');
    }

    public function fetchValue($id, $key) {
        $row = $this->_fetchRow($id, $key);

        if ($row) {
            return $row->value;
        } else {
            return null;
        }
    }

    public function storeValue($id, $key, $value) {
        $this->getTable()->insert(array(
            'user_id' => $id,
            'key' => $key,
            'value' => $value
        ));
    }

    public function updateValue($id, $key, $value) {
        $row = $this->_fetchRow($id, $key);

        if ($row) {
            $row->key = $key;
            $row->value = $value;
            return $row->save();
        } else {
            throw new Exception('key "' . $key . '" or user_id "' . $id . '" not found');
        }
    }

    public function deleteValue($id, $key) {
        $row = $this->_fetchRow($id, $key);

        if ($row) {
            return $row->delete();
        } else {
            throw new Exception('key "' . $key . '" or user_id "' . $id . '" not found');
        }
    }

    protected function _fetchRow($id, $key) {
        $select = $this->getTable()->select();
        $select->where('`user_id` = ?', $id);
        $select->where('`key` = ?', $key);
        $row = $this->getTable()->fetchAll($select)->current();

        if ($row) {
            return $row;
        } else {
            return null;
        }
    }

    /**
     * Stores a given event as detail with the date, the ip and the user.
     * @param int $id
     * @param string $event
     */
    public function logEvent($id, $event) {
        $date = date("Y-m-d\TH:i:s");
        $ip = Daiquiri_Auth::getInstance()->getRemoteAddr();
        $user = Daiquiri_Auth::getInstance()->getCurrentUsername();

        $this->storeValue($id, $event, 'date:' . $date . ',ip:' . $ip . ',user:' . $user);
    }

}

