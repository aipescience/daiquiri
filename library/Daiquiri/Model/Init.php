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

abstract class Daiquiri_Model_Init {

    /**
     * The instance of Daiquiri_Init this model was instantiazed from.
     * @var Daiquiri_Init $_init
     */
    protected $_init;

    /**
     * Constructor. Sets $_init model.
     */
    public function __construct($init) {
        $this->_init = $init;
    }

    /**
     * Returns the acl resources for the module.
     * @return array $resources
     */
    abstract public function getResources();

    /**
     * Returns the acl rules for the module.
     * @return array $rules
     */
    abstract public function getRules();


    /**
     * Processes the modules part of $options['config'].
     */
    abstract public function processConfig();


    /**
     * Processes the modules part of $options['init'].
     */
    abstract public function processInit();

    /**
     * Initializes the database with the init data for this module.
     */
    abstract public function init();

    /**
     * Checks the status of the response of an init call to a model function.
     * @param array $r response array
     * @param array $a input array
     */ 
    protected function _check($r, $a) {
        if ($r['status'] !== 'ok') {
            echo 'ERROR';
            if (array_key_exists('error', $r)) {
                echo ': ' . $r['error'];
            }
            Zend_Debug::dump($a);
            die(0);
        }
    }

    /**
     * Displays an error and quits the script.
     * @param string $error the error string
     */
    protected function _error($error) {
        echo $error . PHP_EOL;
        die(0);
    }

    /**
     * recusively builds the config array.
     */
    protected function _buildConfig_r(&$input, &$output, $defaults) {
        if (is_array($defaults)) {
            if (empty($defaults)) {
                $output = array();
            } else if ($input === false) {
                $output = false;
            } else if (is_array($input)) {
                foreach (array_keys($defaults) as $key) {
                    $this->_buildConfig_r($input[$key], $output[$key], $defaults[$key]);
                    unset($input[$key]);
                }
            } else {
                $output = $defaults;
            }
            if (!empty($input)) {
                if (is_array($input)) {
                    foreach ($input as $key => $value) {
                        $output[$key] = $value;
                    }
                }
            }
        } else {
            if (isset($input)) {
                if (is_array($input)) {
                    $this->_error("Config option '?' is an array but should not.");
                } else {
                    $output = $input;
                    unset($input);
                }
            } else {
                $output = $defaults;
            }
        }
    }
}
