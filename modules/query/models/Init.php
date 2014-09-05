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

class Query_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the query module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Query_Model_Account',
            'Query_Model_Database',
            'Query_Model_Examples',
            'Query_Model_Form',
            'Query_Model_Jobs',
            'Query_Model_Query',
            'Query_Model_Uws'
        );
    }

    /**
     * Returns the acl rules for the query module.
     * @return array $rules
     */
    public function getRules() {
        $rules = array();

        if ($this->_init->options['config']['query']['guest']) {
            $rules['guest'] = array(
                'Query_Model_Form' => array('submit'),
                'Query_Model_Account' => array(
                    'listJobs','showJob','killJob','removeJob','renameJob','databases','functions','examples'
                ),
                'Query_Model_Database' => array(
                    'download', 'file', 'stream', 'regen'
                ),
                'Query_Model_Examples' => array('index', 'show')
            );

            if (strtolower($this->_init->options['config']['query']['processor']['plan']) === 'alterplan' ||
                strtolower($this->_init->options['config']['query']['processor']['plan']) === 'infoplan') {

                $rules['guest']['Query_Model_Form'][] = 'plan';
                $rules['guest']['Query_Model_Form'][] = 'mail';
            }
        } else {
            $rules['user'] = array(
                'Query_Model_Form' => array('submit'),
                'Query_Model_Account' => array(
                    'listJobs','showJob','killJob','removeJob','renameJob','databases','functions','examples'
                ),
                'Query_Model_Database' => array(
                    'download', 'file', 'stream', 'regen'
                ),
                'Query_Model_Examples' => array('index', 'show')
            );

            if (strtolower($this->_init->options['config']['query']['processor']['plan']) === 'alterplan' ||
                strtolower($this->_init->options['config']['query']['processor']['plan']) === 'infoplan') {

                $rules['user']['Query_Model_Form'][] = 'plan';
                $rules['user']['Query_Model_Form'][] = 'mail';
            }
        }

        //$rules['user']['Query_Model_Uws'] = array('getJobList', 'getJob', 'getError', 'createPendingJob', 'getQuote','createJobId', 'getPendingJob', 'getQuote', 'setDestructTime','setDestructTimeImpl', 'setExecutionDuration', 'setParameters','deleteJob', 'abortJob', 'runJob');

        $rules['user']['Query_Model_Uws'] = array('index','get','post','put','delete','head','options');

        $rules['admin'] = array(
            'Query_Model_Jobs' => array('rows','cols','show','kill','remove','rename'),
            'Query_Model_Examples' => array('index','create','update','delete','export')
        );

        return $rules;
    }

    /**
     * Processes the 'query' part of $options['config'].
     */
    public function processConfig() {
        if (!isset($this->_init->input['config']['query'])) {
            $input = array();
        } else if (!is_array($this->_init->input['config']['query'])) {
            $this->_error('Query config options needs to be an array.');
        } else {
            $input = $this->_init->input['config']['query'];
        }

        // create default entries
        $defaults = array(
            'guest' => false,
            'userDb' => array(
                'engine' => 'MyISAM',
            ),
            'forms' => array(
                'sql' => array(
                    'default' => true,
                    'title' => 'SQL query',
                    'help' => 'Place your SQL statement directly in the text area below and submit your request using the button.',
                    'class' => 'Query_Form_SqlQuery',
                    'view' => $this->_init->daiquiri_path . '/modules/query/views/scripts/_partials/sql-query.phtml',
                )
            ),
            'resultTable' => array(
                'placeholder' => '/*@GEN_RES_TABLE_HERE*/'
            ),
            'validate' => array(
                'serverSide' => false,
                'function' => 'paqu_validateSQL'
            ),
            'query' => array(
                'type' => 'direct', // or qqueue
                'qqueue' => array(
                    'defaultUsrGrp' => 'user',
                    'defaultQueue' => 'short'
                )
            ),
            'scratchdb' => '',
            'processor' => array(
                'type' => 'direct', // or mysql or paqu
                'plan' => 'simple', // or infoplan or alterplan
                'mail' => array(
                    'enabled' => false,
                    'mail' => array()
                )
            ),
            'samp' => true,
            'quota' => array(
                'guest' => '100 MB',
                'user' => '500 MB',
                'admin' => '1.5 GB',
            ),
            'download' => array(
                'type' => 'direct', // or gearman
                'dir' => "/var/lib/daiquiri/download",
                'gearman' => array(
                    'port' => '4730',
                    'host' => 'localhost',
                    'numThread' => '2',
                    'pid' => '/var/lib/daiquiri/download/GearmanManager.pid',
                    'workerDir' => $this->_init->daiquiri_path . '/modules/query/scripts/download/worker',
                    'manager' => $this->_init->daiquiri_path . '/library/GearmanManager/pecl-manager.php'
                ),
                'adapter' => array(
                    'default' => 'csv',
                    'enabled' => array('csv'),
                    'config' => array(
                        'mysql' => array(
                            'name' => "MySql database dump",
                            'suffix' => ".sql",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/mysql.sh",
                            'binPath' => '/usr/bin/',
                            'compress' => 'none',
                        ),
                        'csv' => array(
                            'name' => "Comma separated Values",
                            'suffix' => ".csv",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/csv.sh",
                            'binPath' => '/usr/bin/',
                            'compress' => 'none',
                        ),
                        'vodump-csv' => array(
                            'name' => "Comma separated Values",
                            'suffix' => ".csv",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/vodump-csv.sh",
                            'binPath' => '/usr/local/bin/',
                            'compress' => 'none',
                        ),
                        'votable' => array(
                            'name' => "IVOA VOTable XML file - ASCII Format",
                            'suffix' => ".xml",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/votable.sh",
                            'binPath' => '/usr/local/bin/',
                            'compress' => 'none',
                        ),
                        'votableB1' => array(
                            'name' => "IVOA VOTable XML file - BINARY 1 Format",
                            'suffix' => ".xml",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/votable-binary1.sh",
                            'binPath' => '/usr/local/bin/',
                            'compress' => 'none',
                        ),
                        'votableB2' => array(
                            'name' => "IVOA VOTable XML file - BINARY 2 Format",
                            'suffix' => ".xml",
                            'adapter' => $this->_init->daiquiri_path . "/modules/query/scripts/download/adapter/votable-binary2.sh",
                            'binPath' => '/usr/local/bin/',
                            'compress' => 'none',
                        )
                    )
                )
            )
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // process and check
        if (empty($this->_init->options['database']['user'])) {
            $this->_error("No user database adapter specified for query.");
        } else {
            // get prefix and postfix for database
            $split = explode('%', $this->_init->options['database']['user']['dbname']);
            $output['userDb']['prefix'] = $split[0];
            $output['userDb']['postfix'] = $split[1];
        }

        // query.query.type
        $queryType = $output['query']['type'];
        if ($queryType == 'direct') {
            unset($output['query']['qqueue']);
        } else if ($queryType == 'qqueue') {
            // pass
        } else {
            $this->_error("Unknown config value '{$output['query']['type']}' in query.query.type");
        }

        // query.download.type
        if ($output['download']['type'] == 'direct') {
            unset($output['download']['gearman']);
        } else if ($output['download']['type'] == 'gearman') {
            // pass
        } else {
            $this->_error("Unknown value '{$output['download']['type']}' in query.download.queue.type");
        }
        
        // check download adapters
        if (!empty($output['download']['adapter']['enabled'])) {
            foreach ($output['download']['adapter']['enabled'] as $key => $adapter) {
                $config = $output['download']['adapter']['config'][$adapter];
                if ($config['compress'] === false || $config['compress'] === true) {
                    $this->_error("Unknown compression '{$config['compress']}' in query.download.adapter.{$key}. Only 'none', 'zip', 'gzip', 'bzip2', 'pbzip2' allowed.");
                }

                switch ($config['compress']) {
                    case 'none':
                    case 'zip':
                    case 'gzip':
                    case 'bzip2':
                    case 'pbzip2':
                        break;
                    default:
                        $this->_error("Unknown compression '{$config['compress']}' in query.download.adapter.{$key}. Only 'none', 'zip', 'gzip', 'bzip2', 'pbzip2' allowed.");
                        break;
                }
            }
        }

        // set options
        $this->_init->options['config']['query'] = $output;
    }

    /**
     * Processes the 'query' part of $options['init'].
     */
    public function processInit() {
        if (!isset($this->_init->input['init']['query'])) {
            $input = array();
        } else if (!is_array($this->_init->input['init']['query'])) {
            $this->_error('Query init options needs to be an array.');
        } else {
            $input = $this->_init->input['init']['query'];
        }

        // construct examples array
        $output = array('examples' => array());
        if (isset($input['examples'])) {
            if (is_array($input['examples'])) {
                $output['examples'] = $input['examples'];
            } else {
                $this->_error("Query init option 'examples' needs to be an array.");
            }
        }

        $this->_init->options['init']['query'] = $output;
    }

    /**
     * Initializes the database with the init data for the data query.
     */
    public function init() {
        // create config entries
        $queryExamplesModel = new Query_Model_Examples();
        if ($queryExamplesModel->getResource()->countRows() == 0) {
            foreach ($this->_init->options['init']['query']['examples'] as $a) {
                $a['publication_role_id'] = Daiquiri_Auth::getInstance()->getRoleId($a['publication_role']);
                unset($a['publication_role']);

                $r = $queryExamplesModel->create($a);
                $this->_check($r, $a);
            }
        }
    }
}
