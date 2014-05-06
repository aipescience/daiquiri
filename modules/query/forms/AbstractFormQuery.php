<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

    public function getFieldId($string) {
        if (!isset($this->_formOptions['name'])) {
	    throw new Exception('no name was specified');
        }
        return $this->_formOptions['name'] . '_' . $string;
    }
    
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
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        return $adapter->quoteInto($string, $value);
    }

    protected function _escape($string) {
        $username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        return trim($adapter->quote($string), "'");
    }

}
