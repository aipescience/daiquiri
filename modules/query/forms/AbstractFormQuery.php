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

abstract class Query_Form_AbstractFormQuery extends Daiquiri_Form_Abstract {

    protected $_queues = null;
    protected $_defaultQueue = null;
    protected $_tablename = null;
    protected $_formOptions = array();

    public function setQueues($queues) {
        $this->_queues = $queues;
    }

    public function setDefaultQueue($defaultQueue) {
        $this->_defaultQueue = $defaultQueue;
    }

    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    public function setFormOptions(array $formOptions) {
        $this->_formOptions = $formOptions;
    }

    abstract public function getQuery();

    abstract public function getTablename();

    abstract public function getQueue();

    abstract public function getCsrf();
    
    public function addQueueElements($prefix) {
        if (!empty($this->_queues)) {
            $buttons = array();
            foreach ($this->_queues as $key => $queue) {
                if ($queue['name'] === $this->_defaultQueue['name']) {
                    $buttons[] = array(
                        'identifier' => $prefix . $key . "_def",
                        'label' => ucfirst($queue['name']) . ' queue',
                        'tooltip' => "Priority: {$queue['priority']} Timeout: {$queue['timeout']}"
                    );
                } else {
                    $buttons[] = array(
                        'identifier' => $prefix . $key,
                        'label' => ucfirst($queue['name']) . ' queue',
                        'tooltip' => "Priority: {$queue['priority']} Timeout: {$queue['timeout']}"
                    );
                }
            }
            $this->addToggleButtonElements($prefix, $buttons);
        }
    }

    public function addQueueGroup($prefix, $identifier) {
        if (!empty($this->_queues)) {
            $buttons = array();
            foreach ($this->_queues as $key => $queue) {
                if ($queue['name'] === $this->_defaultQueue['name']) {
                    $buttons[] = array(
                        'identifier' => $prefix . $key . "_def",
                    );
                } else {
                    $buttons[] = array(
                        'identifier' => $prefix . $key,
                    );
                }
            }
            $this->addToggleButtonGroup($prefix, $buttons, $identifier);
        }
    }

    protected function _quoteInto($string, $value) {
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        return $adapter->quoteInto($string, $value);
    }

    protected function _escape($string) {
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter($username);
        return trim($adapter->quote($string, $value), "'");
    }

}
