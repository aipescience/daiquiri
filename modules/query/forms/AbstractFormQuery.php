<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

    /**
     * The set of queues to be used with this query form.
     * @var array
     */
    protected $_queues = null;

    /**
     * The default queue for this query form.
     * @var string
     */
    protected $_defaultQueue = null;

    /**
     * The default entry for the tablename field.
     * @var array
     */
    protected $_tablename = null;

    /**
     * The array of options for this query form.
     * @var array
     */
    protected $_formOptions = array();

    /**
     * Sets $_queues.
     * @param array $queues the set of queues to be used with this query form
     */
    public function setQueues($queues) {
        $this->_queues = $queues;
    }

    /**
     * Sets $_defaultQueue.
     * @param string $defaultQueue the default queue for this query form.
     */
    public function setDefaultQueue($defaultQueue) {
        $this->_defaultQueue = $defaultQueue;
    }

    /**
     * Sets $_tablename.
     * @param array $tablename the default entry for the tablename field
     */
    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    /**
     * Sets $_formOptions.
     * @param array $formOptions the array of options for this query form
     */
    public function setFormOptions(array $formOptions) {
        $this->_formOptions = $formOptions;
    }

    /**
     * Gets the SQL query contructed from the form fields.
     * @return string $sql
     */
    abstract public function getQuery();

    /**
     * Gets the content of the tablename field.
     * @return string $tablename
     */
    abstract public function getTablename();

    /**
     * Gets the selected queue.
     * @return string $queue
     */
    abstract public function getQueue();

    /**
     * Returns the form element name of a database field.
     * @param  string $string  database field  
     * @return string $fieldId 
     */
    public function getFieldId($string) {
        if (!isset($this->_formOptions['name'])) {
	    throw new Exception('no name was specified');
        }
        return $this->_formOptions['name'] . '_' . $string;
    }
    
    /**
     * Adds the queue selection buttons to the form.
     * @param string $prefix prefix for this form
     */
    public function addQueueElements($prefix) {
        if (!empty($this->_queues)) {
            $buttons = array();
            foreach ($this->_queues as $key => $queue) {
                if ($queue['name'] === $this->_defaultQueue['name']) {
                    $buttons[] = array(
                        'name' => $prefix . $key . "_def",
                        'label' => ucfirst($queue['name']) . ' queue',
                        'tooltip' => "Priority: {$queue['priority']} Timeout: {$queue['timeout']}"
                    );
                } else {
                    $buttons[] = array(
                        'name' => $prefix . $key,
                        'label' => ucfirst($queue['name']) . ' queue',
                        'tooltip' => "Priority: {$queue['priority']} Timeout: {$queue['timeout']}"
                    );
                }
            }

            if (count($buttons) <= 5) {
                $this->addToggleButtonElements($buttons, $prefix);
            } else {
                $entries = array();
                foreach ($buttons as $button) {
                    $entries[$button['name']] = $button['label'];
                }
                $this->addElement('select', $prefix . 'select', array(
                    'required' => false,
                    'ignore' => false,
                    'decorators' => array('ViewHelper', 'Label'),
                    'multiOptions' => $entries
                ));
            }
        }
    }

    /**
     * Adds the display group for queue selection buttons to the form.
     * @param string $prefix prefix for this form
     * @param string $name   name of the group
     */
    public function addQueueGroup($prefix, $name) {
        if (!empty($this->_queues)) {
            if (count($this->_queues) <= 5) {
                $elements = array();
                foreach ($this->_queues as $key => $queue) {
                    if ($queue['name'] === $this->_defaultQueue['name']) {
                        $elements[] = $prefix . $key . "_def";
                    } else {
                        $elements[] = $prefix . $key;
                    }
                }

                $this->addToggleButtonsGroup($elements, $name, $prefix);
            } else {
                $this->addInlineRightGroup(array($prefix . 'select'), $name);
            }
        }
    }

    /**
     * Quotes a given string using the database adapter.
     * @param  string $string quote string including database field
     * @param  $value $value  value to quote
     * @return string $quotedString
     */
    protected function _quoteInto($string, $value) {
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        return $adapter->quoteInto($string, $value);
    }

    /**
     * Escapes a given string using the database adapter.
     * @param  string $string string to escape
     * @return string $escapedString
     */
    protected function _escape($string) {
        $adapter = Daiquiri_Config::getInstance()->getUserDbAdapter();
        return trim($adapter->quote($string), "'");
    }

}
