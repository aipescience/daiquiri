<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Daiquiri_Log extends Daiquiri_Model_Singleton {

    /**
     * The Zend log object.
     * @var Zend_Log
     */
    private $_log = null;

    /**
     * Constructor. Sets writer and filter for log.
     */
    function __construct() {
        if (Daiquiri_Config::getInstance()->core->log->enabled && php_sapi_name() !== 'cli') {
            // configure formatter for log
            $ip = Daiquiri_Auth::getInstance()->getRemoteAddr();
            $username = Daiquiri_Auth::getInstance()->getCurrentUSername();
            $formatstring = '%timestamp% ' . $ip . ' "' . $username . '" %priorityName% "%message%"' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($formatstring);

            // open log file and get writer for log
            $stream = @fopen(Daiquiri_Config::getInstance()->core->log->logfile, 'a', false);
            if (!$stream) {
                throw new Exception('Failed to open log file');
            }
            $writer = new Zend_Log_Writer_Stream($stream);
            $writer->setFormatter($formatter);

            // set loglevel
            $loglevel = strtoupper(Daiquiri_Config::getInstance()->core->log->loglevel);
            $filter = new Zend_Log_Filter_Priority(constant("Zend_Log::$loglevel"));

            // configure log object
            $this->_log = new Zend_Log();
            $this->_log->addWriter($writer);
            $this->_log->addFilter($filter);
        }
    }

    /**
     * Magic function to proxy all function calls to the log object.
     * @param  string $methodname name of the function to call
     * @param  array  $arguments  arguments for the function call
     * @return type $returnvalue
     */
    public function __call($methodname, $arguments) {
        if (Daiquiri_Config::getInstance()->core->log->enabled && php_sapi_name() !== 'cli') {
            return call_user_func_array(array($this->_log, $methodname), $arguments);
        }
    }
}
