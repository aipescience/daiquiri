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

class Config_Model_Init extends Daiquiri_Model_Init {

    public function parseOptions(array $options) {
        $options = $this->_parseConfigOptions($options);
        $options = $this->_parseTemplatesOptions($options);
        return $options;
    }

    private function _parseConfigOptions($options) {

        if (!isset($this->_input_options['config'])) {
            $this->_error("No config options provided.");
        } else if (!is_array($this->_input_options['config'])) {
            $this->_error('Config options needs to be an array.');
        } else {
            $input = $this->_input_options['config'];
        }

        // create default config
        $defaults = array(
            'core' => array(
                'captcha' => array(
                    'fontpath' => $this->_daiquiri_path . "/client/font/DroidSans.ttf",
                    'dir' => "/var/lib/daiquiri/captcha",
                    'url' => "/captcha",
                ),
                'minify' => array(
                    'enabled' => false
                ),
                'libs' => array(
                    'phpSqlParser' => $this->_daiquiri_path . '/library/PHP-SQL-Parser/',
                    'paqu' => $this->_daiquiri_path . '/library/paqu/src/',
                    'PHPZip' => $this->_daiquiri_path . '/library/PHPZip',
                ),
                'system' => array(
                    'mysql' => array(
                        'socket' => '/var/run/mysqld/mysqld.sock'
                    )
                )
            ),
            'auth' => array(
                'registration' => false,
                'confirmation' => false,
                'password' => array(
                    'default' => array(
                        'algo' => 'cryptSha512'
                    )
                ),
                'details' => array(
                    'firstname',
                    'lastname'
                ),
                'timeout' => 0,
                'disableOnForgotPassword' => true,
                'changeEmail' => true,
                'lowerCaseUsernames' => false,
                'usernameMinLength' => 4,
                'passwordMinLength' => 4
            ),
            'cms' => array(
                'enabled' => false,
                'type' => 'wordpress',
                'url' => '/cms/',
                'path' => '/var/lib/daiquiri/wordpress'
            ),
            'query' => array(
                'adapter' => 'user',
                'guest' => false,
                'userDb' => array(
                    'prefix' => '',
                    'postfix' => '',
                    'engine' => 'MyISAM',
                ),
                'forms' => array(
                    'sql' => array(
                        'default' => true,
                        'title' => 'SQL query',
                        'help' => 'Place your SQL statement directly in the text area below and submit your request using the button.',
                        'class' => 'Query_Form_SqlQuery',
                        'view' => $this->_daiquiri_path . '/modules/query/views/scripts/_partials/sql-query.phtml',
                    )
                ),
                'examples' => array(),
                'resultTable' => array(
                    'placeholder' => '/*@GEN_RES_TABLE_HERE*/'
                ),
                'validate' => array('serverSide' => false,
                    'function' => 'paqu_validateSQL'
                ),
                'queue' => array(
                    'type' => 'simple',
                    'qqueue' => array(
                        'defaultUsrGrp' => 'user',
                        'defaultQueue' => 'short'
                    )
                ),
                'scratchdb' => '',
                'processor' => array(
                    'name' => 'direct',
                    'type' => 'simple',
                    'mail' => array(
                        'enabled' => false,
                        'mail' => array()
                    )
                ),
                'quota' => array(
                    'guest' => '100MB',
                    'user' => '500MB',
                    'admin' => '1.5GB',
                ),
                'download' => array(
                    'dir' => "/var/lib/daiquiri/download",
                    'queue' => array(
                        'type' => 'simple',
                        'gearman' => array(
                            'port' => '4730',
                            'host' => 'localhost',
                            'numThread' => '2',
                            'pid' => '/var/lib/daiquiri/download/GearmanManager.pid',
                            'workerDir' => $this->_daiquiri_path . '/modules/query/scripts/download/worker',
                            'manager' => $this->_daiquiri_path . '/library/GearmanManager/pecl-manager.php'
                        )
                    ),
                    'adapter' => array(
                        'enabled' => array(
                            'csv'
                        ),
                        'config' => array(
                            'mysql' => array(
                                'name' => "MySql database dump",
                                'suffix' => ".sql",
                                'adapter' => $this->_daiquiri_path . "/modules/query/scripts/download/adapter/mysql.sh",
                                'binPath' => '/usr/bin/',
                                'compress' => 'none',
                            ),
                            'csv' => array(
                                'name' => "Comma separated Values",
                                'suffix' => ".csv",
                                'adapter' => $this->_daiquiri_path . "/modules/query/scripts/download/adapter/csv.sh",
                                'binPath' => '/usr/bin/',
                                'compress' => 'none',
                            ),
                            'votable' => array(
                                'name' => "IVOA VOTable XML file - ASCII Format",
                                'suffix' => ".xml",
                                'adapter' => $this->_daiquiri_path . "/modules/query/scripts/download/adapter/votable.sh",
                                'binPath' => '/usr/local/bin/',
                                'compress' => 'none',
                            ),
                            'votableB1' => array(
                                'name' => "IVOA VOTable XML file - BINARY 1 Format",
                                'suffix' => ".xml",
                                'adapter' => $this->_daiquiri_path . "/modules/query/scripts/download/adapter/votable-binary1.sh",
                                'binPath' => '/usr/local/bin/',
                                'compress' => 'none',
                            ),
                            'votableB2' => array(
                                'name' => "IVOA VOTable XML file - BINARY 2 Format",
                                'suffix' => ".xml",
                                'adapter' => $this->_daiquiri_path . "/modules/query/scripts/download/adapter/votable-binary2.sh",
                                'binPath' => '/usr/local/bin/',
                                'compress' => 'none',
                            )
                        )
                    )
                )
            ),
            'contact' => true,
            'data' => array(
                'writeToDB' => 0,
                'viewer' => array(
                    'removeNewline' => false,
                    'columnWidth' => '80em'
                )
            ),
            'files' => array(),
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // check auth
        if (!empty($output['auth']['confirmation'])) {
            $output['auth']['registration'] = true;
        }

        // check queue
        if (!empty($output['query'])) {

            if (empty($options['database']['user'])) {
                $this->_error("No user database adapter specified for query.");
            } else {
                // get prefix and postfix for database
                $split = explode('%', $options['database']['user']['dbname']);
                $output['query']['userDb']['prefix'] = $split[0];
                $output['query']['userDb']['postfix'] = $split[1];
            }

            $queueType = $output['query']['queue']['type'];
            if ($queueType == 'simple') {
                unset($output['query']['queue']['qqueue']);
            } else if ($queueType == 'qqueue') {
                // pass
            } else {
                $this->_error("Unknown config value '{$output['query']['queue']['type']}' in query.queue.type");
            }

            // query.downloadQueue
            if ($output['query']['download']['queue']['type'] == 'simple') {
                unset($output['query']['download']['queue']['gearman']);
            } else if ($output['query']['download']['queue']['type'] == 'gearman') {
                // pass
            } else {
                $this->_error("Unknown value '{$output['query']['download']['queue']['type']}' in query.download.queue.type");
            }
        }

        // check download adapters
        foreach ($output['query']['download']['adapter']['enabled'] as $key => $adapter) {
            $config = $output['query']['download']['adapter']['config'][$adapter];
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

        $options['config'] = $output;
        return $options;
    }

    private function _buildConfig_r(&$input, &$output, $defaults) {
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

    private function _parseTemplatesOptions($options) {

        if (!isset($this->_input_options['templates'])) {
            $input = array();
        } else if (!is_array($this->_input_options['templates'])) {
            $this->_error('Templates options need to be an array.');
        } else {
            $input = $this->_input_options['templates'];
        }

        $defaults = array(
            'auth.register' => array(
                'subject' => '[Daiquiri] User Registration',
                'body' => 'Dear _firstname_ _lastname_,

Thank you for registering. 

Please click on the following link to validate your registration:

_link_

After validation, the request will be processed by the administrators. This can take one or two days.

Best Regards'
            ),
            'auth.forgotPassword' => array(
                'subject' => '[Daiquiri] Forgotten password validation',
                'body' => 'Dear _firstname_ _lastname_,

Please click on the following link to change your password:

_link_

Best Regards'
            ),
            'auth.resetPassword' => array(
                'subject' => '[Daiquiri] password reset by user',
                'body' => 'Dear Daiquiri-Test manager,

_firstname_ _lastname_ has just reset his/her password. 

Please login to the admin interface to re-enable the user.

_link_

Best Regards'
            ),
            'auth.validate' => array(
                'subject' => '[Daiquiri] New User registered',
                'body' => 'Dear Daiquiri-Test manager,

_firstname_ _lastname_ has just validated his/her registration. 

Please login to the admin interface and confirm the user.

_link_

Best Regards'
            ),
            'auth.confirm' => array(
                'subject' => '[Daiquiri] User account confirmed',
                'body' => 'Dear Daiquiri-Test manager,

_firstname_ _lastname_ has just been confirmed by a manager (username: _manager_).

Best Regards'
            ),
            'auth.reject' => array(
                'subject' => '[Daiquiri] User account rejected',
                'body' => 'Dear Daiquiri-Test manager,

_firstname_ _lastname_ has just been rejected by a manager (username: _manager_).

Best Regards'
            ),
            'auth.activate' => array(
                'subject' => '[Daiquiri] User account activated',
                'body' => 'Dear _firstname_ _lastname_,

your account has been created and you should be able to log in. 

Best Regards'
            ),
            'auth.reenable' => array(
                'subject' => '[Daiquiri] User account re-enabled',
                'body' => 'Dear _firstname_ _lastname_,

your account has been enabled again. If your account was disabled in the process of
setting a new password, you can now use the new password to log in.

Best Regards'
            ),
            'contact.submit_user' => array(
                'subject' => '[Daiquiri] Contact message',
                'body' => 'Dear _firstname_ _lastname_,

Thank you for using our contact form. A message has been sent to the responsible supporters of this project.
If you do not receive a reply within 2-3 work days, please contact us again.

Best Regards'
            ),
            'contact.submit_support' => array(
                'subject' => '[Daiquiri] New contact message',
                'body' => 'Dear Daiquiri-Test support member,

a new message was sent via the contact form.

---

From:     _firstname_ _lastname_
Email:    _email_
Category: _category_
Subject:  _subject_

_message_

---

Please login to the admin interface to reply to the message the user.

_link_

Best Regards'
            ),
            'contact.respond' => array(
                'subject' => '[Daiquiri] Re: _subject_',
                'body' => 'Dear _firstname_ _lastname_,



Best Regards'
            ),
            'query.plan' => array(
                'subject' => '[Daiquiri] Query Plan Inquery',
                'body' => 'Dear Daiquiri developer,

a message was send via the contact form of the query plan editor:

---

Original Query:

_sql_

Query Plan:

_plan_

---

From:     _firstname_ _lastname_
Email:    _email_

_message_

---

Best Regards'
            )
        );

        $output = array();

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $input)) {
                if (is_array($input[$key])) {
                    $output[$key] = $input[$key];
                } else {
                    $this->_error("Templates option 'templates.$key' needs to be an array.");
                }
            } else {
                $output[$key] = $value;
            }
        }

        $options['templates'] = $output;
        return $options;
    }

    public function init(array $options) {
        // create config entries
        $configEntryModel = new Config_Model_Entries();
        if (count($configEntryModel->index()) == 0) {
            $entries = array();
            $this->_buildConfigEntries_r($entries, $options['config'], array());
            foreach ($entries as $key => $value) {
                $a = array(
                    'key' => $key,
                    'value' => $value
                );
                $r = $configEntryModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create templates
        if (!empty($options['templates'])) {
            $templatesModel = new Config_Model_Templates();
            if (count($templatesModel->getValues()) == 0) {
                foreach ($options['templates'] as $key => $value) {
                    $a = array(
                        'template' => $key,
                        'subject' => $value['subject'],
                        'body' => $value['body']
                    );
                    $r = $templatesModel->create($a);
                    $this->_check($r, $a);
                }
            }
        }
    }

    private function _buildConfigEntries_r(&$entries, &$config, $keys) {
        if (is_array($config)) {
            foreach (array_keys($config) as $key) {
                $this->_buildConfigEntries_r($entries, $config[$key], array_merge($keys, array($key)));
            }
        } else {
            $entries[implode('.', $keys)] = $config;
        }
    }

}
