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

abstract class Daiquiri_Model_Init {

    protected $_application_path;
    protected $_daiquiri_path;
    protected $_input_options = array();

    public function __construct($application_path, $daiquiri_path, $input_options) {
        $this->_application_path = $application_path;
        $this->_daiquiri_path = $daiquiri_path;
        $this->_input_options = $input_options;
    }

    abstract public function parseOptions(array $options);

    abstract public function init(array $options);

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

    protected function _error($string) {
        echo $string . PHP_EOL;
        die(0);
    }

}

