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

    /**
     * Returns the acl resources for the meetings module.
     * @return array $resources
     */
    public function getResources() {
        return array(
            'Admin_IndexController',
            'Config_Model_Entries',
            'Config_Model_Messages',
            'Config_Model_Templates'
        );
    }

    /**
     * Returns the acl rules for the meeitngs module.
     * @return array $rules
     */
    public function getRules() {
        $rules = array(
            'admin' => array(
                'Admin_IndexController' => array('index'),
                'Config_Model_Entries' => array('index', 'create', 'update', 'delete', 'export'),
                'Config_Model_Messages' => array('index', 'create', 'update', 'delete', 'export'),
                'Config_Model_Templates' => array('index', 'create', 'update', 'delete', 'export')
            )
        );
        if (in_array('contact',$this->_init->options['modules'])) {
            $rules['support']['Admin_IndexController'] = array('index');
        }
        if ($this->_init->options['config']['auth']['confirmation']) {
            $rules['manager']['Admin_IndexController'] = array('index');
        }
        return $rules;
    }

    /**
     * Processes the 'core' part of $options['config'].
     */
    public function processConfig() {
        if (!isset($this->_init->input['config']['core'])) {
            $input = array();
        } else if (!is_array($this->_init->input['config']['core'])) {
            $this->_error('Core config options need to be an array.');
        } else {
            $input = $this->_init->input['config']['core'];
        }

        // create default entries
        $defaults = array(
            'captcha' => array(
                'fontpath' => $this->_init->daiquiri_path . "/client/font/DroidSans.ttf",
                'dir' => "/var/lib/daiquiri/captcha",
                'url' => "/captcha",
            ),
            'minify' => array(
                'enabled' => false
            ),
            'libs' => array(
                'phpSqlParser' => $this->_init->daiquiri_path . '/library/PHP-SQL-Parser/',
                'paqu' => $this->_init->daiquiri_path . '/library/paqu/src/',
                'PHPZip' => $this->_init->daiquiri_path . '/library/PHPZip',
            ),
            'system' => array(
                'mysql' => array(
                    'socket' => '/var/run/mysqld/mysqld.sock'
                )
            ),
            'cms' => array(
                'enabled' => false,
                'url' => '/cms/',
                'type' => 'wordpress',
                'path' => '/var/lib/daiquiri/wordpress',
                'navPath' => '/var/lib/daiquiri/navigation',
            )
        );

        // create config array
        $output = array();
        $this->_buildConfig_r($input, $output, $defaults);

        // set options
        $this->_init->options['config']['core'] = $output;
    }

    /**
     * Processes the 'templates' and 'status' part of $options['init'].
     */
    public function processInit() {
        $this->_processTemplatesInit();
        $this->_processMessagesInit();
    }

    /**
     * Processes the 'templates' part of $options['init'].
     */
    private function _processTemplatesInit() {
        if (!isset($this->_init->options['init']['templates'])) {
            $input = array();
        } else if (!is_array($this->_init->options['init']['templates'])) {
            $this->_error('Templates init options need to be an array.');
        } else {
            $input = $this->_init->options['init']['templates'];
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
                'subject' => '[Daiquiri] Password reset by user',
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
            ),
            'meetings.validate' => array(
                'subject' => '_meeting_',
                'body' => 'Dear _firstname_ _lastname_,

Thank you for registering for the meeting. 

Please click on the following link to validate your registration:

_link_

Best Regards'
            ),
            'meetings.register' => array(
                'subject' => '_meeting_',
                'body' => 'Dear _firstname_ _lastname_,

Thank you for registering for the meeting. 

We have stored the following information about your registration:

Personal data
-------------
Firstname:   _firstname_
lastname:    _lastname_
Affiliation: _affiliation_
Email:       _email_
Arrival:     _arrival_
Departure:   _departure_

Best Regards'
            ),
            'meetings.accept' => array(
                'subject' => '_meeting_',
                'body' => 'Dear _firstname_ _lastname_,

Yes!

Best Regards'
            ),
            'meetings.reject' => array(
                'subject' => '_meeting_',
                'body' => 'Dear _firstname_ _lastname_,

No!

Best Regards'
            )
        );

        $output = array();

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $input)) {
                if (is_array($input[$key])) {
                    $output[$key] = $input[$key];
                } else {
                    $this->_error("Templates init option 'templates.$key' needs to be an array.");
                }
            } else {
                $output[$key] = $value;
            }
        }

        $this->_init->options['init']['templates'] = $output;
    }

    /**
     * Processes the 'messages' part of $options['init'].
     */
    private function _processMessagesInit() {
        if (!isset($this->_init->options['init']['messages'])) {
            $input = array();
        } else if (!is_array($this->_init->options['init']['messages'])) {
            $this->_error('Message init options needs to be an array.');
        } else {
            $input = $this->_init->options['init']['messages'];
        }

        // create default config
        $defaults = array(
            'query' => ''
        );

        $output = array();

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $input)) {
                $output[$key] = $input[$key];
            } else {
                $output[$key] = $value;
            }
        }

        $this->_init->options['init']['messages'] = $output;
    }

    /**
     * Initializes the database with the init data for the config module.
     */
    public function init() {
        // create config entries
        $entriesModel = new Config_Model_Entries();
        if ($entriesModel->getResource()->countRows() == 0) {
            $entries = array();
            $this->_buildConfigEntries_r($entries, $this->_init->options['config'], array());
            foreach ($entries as $key => $value) {
                $a = array(
                    'key' => $key,
                    'value' => $value
                );
                $r = $entriesModel->create($a);
                $this->_check($r, $a);
            }
        }

        // create templates
        if (!empty($this->_init->options['templates'])) {
            $templatesModel = new Config_Model_Templates();
            if ($templatesModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['templates'] as $key => $value) {
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

        // create messages
        if (!empty($this->_init->options['messages'])) {
            $messagesModel = new Config_Model_Messages();
            if ($messagesModel->getResource()->countRows() == 0) {
                foreach ($this->_init->options['messages'] as $key => $value) {
                    $a = array(
                        'key' => $key,
                        'value' => $value
                    );
                    $r = $messagesModel->create($a);
                    $this->_check($r, $a);
                }
            }
        }
    }

    /**
     * Recusively builds the config entries to be inserted into the database.
     */
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
