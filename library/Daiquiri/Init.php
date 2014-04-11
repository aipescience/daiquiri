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
 * @class   Daiquiri_Init Init.php
 * @brief   Daiquiri initialisation process handler.
 * 
 * Daiquiri initialisation process handler. The big class that does all the setup
 * magic... And it also works as a CLI tool.
 *
 * The main philosophy of the init process and the settings files is, that a default
 * set of settings is provided in this file (any new settings you might add need to have
 * default settings defined in this file) and can be overridden in the config file.
 *
 */
class Daiquiri_Init {

    protected static $_modules = array(
        'config' => array(),
        'auth' => array('config'),
        'contact' => array('config'),
        'data' => array('auth','config'),
        'meetings' => array('auth','config'),
        'query' => array('data','auth','config'),
    );

    protected static $_commandline_options = array(
        'h|help' => 'Displays usage information.',
        'a|application' => 'Creates the application.ini file (must be invoked first).',
        'l|links' => 'Creates the neccessary softlinks.',
        'm|minify' => 'Minifies the static js and css files.',
        'u|user' => 'Displays the commands to create the database user.',
        'c|clean' => 'Displays the commands to clean the database.',
        'v|vhost' => 'Displays the virtual host configuration.',
        'd|drop' => 'Drops the databases including the user databases.',
        's|sync' => 'Creates the databases and tables as long as they do not exist yet.',
        'i|init' => 'Runs the initalisation process.'
    );

    public $application_path;
    public $daiquiri_path;

    public $options;
    public $input;

    public $models = array();
    protected $_opts = array();

    /**
     * Constructor. Sets options.
     * @param string $$application_path
     * @param string $daiquiri_path
     * @param array $input
     */
    public function __construct($application_path, $daiquiri_path, $input) {
        // setup autoloader
        require_once('Zend/Loader/Autoloader.php');
        Zend_Loader_Autoloader::getInstance();

        $this->application_path = $application_path;
        $this->daiquiri_path = $daiquiri_path;
        $this->input = $input;

        $this->_parseCommandLine();

        // init the options array with database and mail options
        $this->options = array();
        $this->_processDatabaseOptions();
        $this->_processMailOptions();
        $this->_processModulesOptions();

        if ($this->_opts === array('application')) {
            // pass
        } else {
            // setup zend application environment 
            $this->_setupEnvironment();

            // get init models from the modules
            foreach($this->options['modules'] as $module) {
                $classname = ucfirst($module) . '_Model_Init';
                $this->models[] = new $classname($this);
            }

            // parse the config array for each model 
            $this->options['config'] = array();
            foreach($this->models as $model) {
                $model->processConfig();
            }
            
            // parse the init array for each model
            $this->options['init'] = array();
            foreach($this->models as $model) {
                $model->processInit();
            }
        }
    }

    /**
     * The function that does all the magic of setting the app up.
     */
    public function run() {
        // run functions
        foreach ($this->_opts as $opt) {
            $method = '_' . $opt;
            $this->$method();
        }
    }

    /**
     * Parses the comment line.
     */
    private function _parseCommandLine() {
        try {
            $opts = new Zend_Console_Getopt(Daiquiri_Init::$_commandline_options);
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
            exit($e->getMessage() . "\n\n" . $e->getUsageMessage());
        }

        foreach (array_keys(Daiquiri_Init::$_commandline_options) as $key) {
            $arr = explode('|', $key);
            if (isset($opts->$arr[0])) {
                $this->_opts[] = $arr[1];
            }
        }

        // check for application option
        if (in_array('application', $this->_opts) && count($this->_opts) > 1) {
            echo "Error: application.ini must be created first." . PHP_EOL;
            echo "Please run ./init.php with -a|--application as the only option." . PHP_EOL;
            die();
        }

        // show help message
        if (isset($opts->h) || count($this->_opts) == 0) {
            echo $opts->getUsageMessage();
            exit;
        }
    }

    /**
     * Sets up the Zend environment.
     */
    private function _setupEnvironment() {
        // setup variables
        define('APPLICATION_PATH', $this->application_path . '/application');
        define('APPLICATION_ENV', 'development');
        // check if the application.ini.file is present
        if (!is_file(APPLICATION_PATH . '/configs/application.ini')) {
            echo "Error: No application.ini file found." . PHP_EOL . "Please run ./init.php -a first." . PHP_EOL;
            die(0);
        }

        // set include path
        set_include_path($this->daiquiri_path . '/library/' . PATH_SEPARATOR . get_include_path());

        // add daiquiri namespaces to autoloader
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Daiquiri_');
        foreach ($this->options['modules'] as $module) {
            $autoloader->registerNamespace(ucfirst($module) . '_');
        }

        // initialize Zend_Application and bootstrap
        $this->_zend_application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/application.ini'
        );

        // fake request
        $front = $this->_zend_application->getBootstrap()
                ->bootstrap('frontController')
                ->getResource('frontController');

        $request = new Daiquiri_Controller_Request_Init();
        $front->setRequest($request);

        $this->_zend_application->bootstrap();
    }

    /**
     * Processes the 'database' part of $options.
     */
    private function _processDatabaseOptions() {
        if (!isset($this->input['database'])) {
            $this->_error("No database options provided.");
        } else if (!is_array($this->input['database'])) {
            $this->_error('Database options needs to be an array.');
        } else {
            $input = $this->input['database'];
        }

        $database_defaults = array(
            'host' => null,
            'dbname' => null,
            'username' => null,
            'password' => null,
            'port' => 3306,
            'additional' => array(),
            'scratchdb' => array(),
            'mysql' => '/usr/bin/mysql',
            'file' => false,
            'func' => false,
            'qqueue' => false
        );

        // loop over database adapters 'web' and 'user'
        foreach (array('web', 'user') as $adapter) {
            if (!empty($input[$adapter]) && is_array($input[$adapter])) {
                // sanity check for loaclhost
                if ($input[$adapter]['host'] === 'localhost'
                        && isset($input[$adapter]['port'])) {
                    $input[$adapter]['host'] = '127.0.0.1';
                }
                $output[$adapter] = $database_defaults;
                foreach (array_keys($input[$adapter]) as $key) {
                    if (array_key_exists($key, $database_defaults)) {
                        $output[$adapter][$key] = $input[$adapter][$key];
                    } else {
                        $this->_error("Database adapter option '$key' is not supported.");
                    }
                }
                // some checks
                foreach (array('host', 'dbname', 'username', 'password') as $key) {
                    if (empty($output[$adapter][$key])) {
                        $this->_error("Missing '$key' in database adapter options.");
                    }
                }
            }
        }

        $this->options['database'] = $output;
    }

    /**
     * Processes the 'mail' part of $options.
     */
    private function _processMailOptions() {
        if (!isset($this->input['mail'])) {
            $this->_error("No mail options provided.");
        } else if (!is_array($this->input['mail'])) {
            $this->_error('Mail options needs to be an array.');
        } else {
            $input = $this->input['mail'];
        }

        $mail_defaults = array(
            'email' => null,
            'name' => null,
            'host' => null,
            'type' => 'smtp',
            'port' => 25,
            'ssl' => null,
            'auth' => null,
            'username' => null,
            'password' => null,
        );
        $output = $mail_defaults;
        foreach ($input as $key => $value) {
            if (array_key_exists($key, $output)) {
                $output[$key] = $input[$key];
            } else {
                $this->_error("Mail adapter option '$key' is not supported.");
            }
        }
        // check that certain keys are not empty
        foreach (array('type', 'host', 'port', 'email', 'name') as $key) {
            if (empty($output[$key])) {
                $this->_error("No $key given in mail options.");
            }
        }

        $this->options['mail'] = $output;
    }

    /**
     * Processes the 'modules' part of $options. Sets the correct modules in the 
     * right order based on the input and the dependencies. 
     */
    private function _processModulesOptions() {
        if (!isset($this->input['modules'])) {
            $this->_error("No modules specified in options.");
        } else if (!is_array($this->input['modules'])) {
            $this->_error('Modules needs to be an array.');
        } else {
            $input = $this->input['modules'];
        }

        $output = array();
        foreach (array_keys(Daiquiri_Init::$_modules) as $module) {
            // add module if it is in the input list, but only if it is not already there
            if (in_array($module, $input) && !in_array($module, $output)) {
                $output[] = $module;
            }

            // add dependencies (no recursion, we are lazy)
            foreach ($input as $input_module) {
                // see if the current module is a dependency
                $dependencies = Daiquiri_Init::$_modules[$input_module];

                // add module to the list if it is a dependency,
                // but again only if it is not already there
                if (in_array($module, $dependencies) && !in_array($module, $output)) {
                    $output[] = $module;
                }
            }
        }

        $this->options['modules'] = $output;
    }

    /**
     * Creates the application.ini file
     */
    private function _application() {
        echo "Creating application.ini file." . PHP_EOL;

        // prepare first part of application.ini
        $output = array(
            '[production]',
            'phpSettings.display_startup_errors = 0',
            'phpSettings.display_errors = 0',
            'bootstrap.path = APPLICATION_PATH "/Bootstrap.php"',
            'bootstrap.class = "Bootstrap"',
            'appnamespace = "Application"',
            'resources.frontController.controllerDirectory = APPLICATION_PATH "/controllers"',
            'resources.frontController.params.displayExceptions = 0',
            'resources.view[] = ',
            'resources.layout.layoutPath = APPLICATION_PATH "/layouts/scripts/"',
            ''
        );

        // prepare database configuration part of application.ini
        foreach ($this->options['database'] as $adapter => $database) {
            $output[] = "resources.multidb.$adapter.adapter = Pdo_Mysql";
            $output[] = "resources.multidb.$adapter.charset = utf8";
            if ($adapter === 'web') {
                $output[] = "resources.multidb.$adapter.default = true";
            }
            foreach (array('dbname', 'username', 'password', 'host') as $key) {
                $output[] = "resources.multidb.$adapter.$key = {$database[$key]}";
            }
            if ($database['host'] !== 'localhost') {
                $output[] = "resources.multidb.$adapter.port = {$database['port']}";
            }
            $output[] = '';
        }

        // prepare mail configuration part of application.ini
        foreach ($this->options['mail'] as $key => $value) {
            if (in_array($key, array('email', 'name'))) {
                $output[] = "resources.mail.defaultFrom.$key = $value";
            } else {
                if (!empty($value)) {
                    $output[] = "resources.mail.transport.$key = $value";
                }
            }
        }
        $output[] = '';

        // prepare final part of application.ini
        $output = array_merge($output, array(
            'includePaths.library = APPLICATION_PATH "/../../daiquiri/library"',
            'autoloadernamespaces[] = "Daiquiri"',
            'resources.frontController.moduleDirectory = APPLICATION_PATH "/../../daiquiri/modules"',
            'resources.modules[] = ',
            'resources.view.helperPath.Daiquiri_View_Helper = APPLICATION_PATH "/../../daiquiri/library/Daiquiri/View/Helper"',
            '',
            '[staging : production]',
            'testing : production',
            'phpSettings.display_startup_errors = 1',
            'phpSettings.display_errors = 1',
            '',
            '[development : production]',
            'phpSettings.display_startup_errors = 1',
            'phpSettings.display_errors = 1',
            'resources.frontController.params.displayExceptions = 1'
                )
        );

        // write application.ini into file
        $filename = $this->application_path . '/application/configs/application.ini';
        if (!$handle = fopen($filename, "w")) {
            throw new Exception("application/configs/application.ini can not be opened for write");
        }
        for ($i = 0; $i < count($output); $i++) {
            if (!fwrite($handle, $output[$i] . PHP_EOL)) {
                throw new Exception("error while writing line $i.");
            }
        }
        fclose($handle);
    }

    /**
     * Creates the necessary softlinks
     */
    private function _links() {
        // captcha directory
        $captcha = $this->application_path . '/public/captcha';
        $links[$captcha] = $this->options['config']['core']['captcha']['dir'];

        // client js and css directory
        $client = $this->application_path . '/public/daiquiri';
        if (!empty($this->options['config']['core']['minify'])
            && $this->options['config']['core']['minify']['enabled'] == true) {

            $links[$client] = null;
        } else {
            $links[$client] = $this->daiquiri_path . '/client';
        }

        // cms (word press directory)
        if (!empty($this->options['config']['core']['cms']) && $this->options['config']['core']['cms']['enabled']) {
            $cms = $this->application_path . '/public' . $this->options['config']['core']['cms']['url'];
            $links[$cms] = $this->options['config']['core']['cms']['path'];
        } else {
            $links[$cms] = null;
        }

        // loop over array, delete the old links and create new links
        foreach ($links as $rawlink => $target) {
            $link = rtrim($rawlink, '/');
            if (is_link($link)) {
                unlink($link);
            }
            if (!empty($target)) {
                if (!file_exists($target)) {
                    echo 'Error: ' . $target . ' does not exist.' . PHP_EOL;
                    die(0);
                } else {
                    echo "creating symlink " . $target . ' -> ' . $link . PHP_EOL;
                    symlink($target, $link);
                }
            }
        }
    }

    /**
     * Minifies the static js and css files.
     */
    private function _minify() {
        $client = $this->daiquiri_path . '/client/';

        if (!file_exists('public/min')) {
            mkdir('public/min');
        }

        echo "minifing js and css files.";

        exec("echo '/* Automatically created file. Manual customization is not recommended. */' > public/min/daiquiri.js" );
        exec("echo '/* Automatically created file. Manual customization is not recommended. */' > public/min/daiquiri.css");

        foreach (Daiquiri_View_Helper_HeadDaiquiri::$files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'js') {
                exec("yui-compressor " . $client . "/" . $file . " >> public/min/daiquiri.js");
            } else if ($ext === 'css') {
                exec("yui-compressor " . $client . "/" . $file . " >> public/min/daiquiri.css");
            }
        }

        // take care of images
        foreach (Daiquiri_View_Helper_HeadDaiquiri::$img as $img) {
            $target = $client . $img;
            $link = $this->application_path . '/public/img/' . basename($img);
            if (is_link($link)) {
                unlink($link);
            }
            if (!file_exists($target)) {
                echo 'Error: ' . $target . ' does not exist.' . PHP_EOL;
                die(0);
            } else {
                echo "creating symlink " . $target . ' -> ' . $link . PHP_EOL;
                symlink($target, $link);
            }
        }
    }

    /**
     * Displays the commands to delete the database.
     */
    private function _clean() {
        foreach ($this->options['database'] as $db) {
            echo "DELETE FROM mysql.user where User='{$db['username']}';" . PHP_EOL;
            echo "DELETE FROM mysql.tables_priv where User='{$db['username']}';" . PHP_EOL;
            echo "DELETE FROM mysql.db where User='{$db['username']}';" . PHP_EOL;
            echo PHP_EOL;
        }
        echo 'FLUSH PRIVILEGES;' . PHP_EOL;
    }

    /**
     * Displays the commands to create the database user.
     */
    private function _user() {
        foreach ($this->options['database'] as $dbkey => $db) {
            // fix localhost confusion of mysql
            $localhost = array('localhost', '127.0.0.1', '::1');
            if (in_array($db['host'], $localhost)) {
                $db['hosts'] = $localhost;
            } else {
                $db['hosts'] = array($db['host']);
            }

            $output = array();
            foreach ($db['hosts'] as $host) {
                $output[$host] = array();
                $output[$host][] = "CREATE USER `{$db['username']}`@`{$host}` IDENTIFIED BY '{$db['password']}';";

                $output[$host][] = "GRANT ALL PRIVILEGES ON `{$db['dbname']}`.* to `{$db['username']}`@`{$host}`;";

                if ($dbkey === 'web') {
                    foreach ($this->options['database'] as $currDb) {
                        foreach ($currDb['additional'] as $dbname) {
                            if (empty($this->options['config']['data']['writeToDB']) ||
                                    $this->options['config']['data']['writeToDB'] === 0) {

                                $output[$host][] = "GRANT SELECT ON `{$dbname}`.* to `{$db['username']}`@`{$host}`;";
                            } else {
                                $output[$host][] = "GRANT SELECT, ALTER ON `{$dbname}`.* to `{$db['username']}`@`{$host}`;";
                            }
                        }
                    }
                } else {
                    foreach ($db['additional'] as $dbname) {
                        if (empty($this->options['config']['data']['writeToDB']) ||
                                $this->options['config']['data']['writeToDB'] === 0) {

                            $output[$host][] = "GRANT SELECT ON `{$dbname}`.* to `{$db['username']}`@`{$host}`;";
                        } else {
                            $output[$host][] = "GRANT SELECT, ALTER ON `{$dbname}`.* to `{$db['username']}`@`{$host}`;";
                        }
                    }
                }

                foreach ($db['scratchdb'] as $dbname) {
                    $output[$host][] = "GRANT ALL PRIVILEGES ON `{$dbname}`.* to `{$db['username']}`@`{$host}`;";
                }

                if ($db['file'] === true) {
                    $output[$host][] = "GRANT FILE ON *.* TO `{$db['username']}`@`{$host}`;";
                }
                if ($db['func'] === true) {
                    $output[$host][] = "GRANT SELECT ON `mysql`.func to `{$db['username']}`@`{$host}`;";
                }
                if ($db['qqueue'] === true) {
                    $output[$host][] = "GRANT SELECT, UPDATE ON `mysql`.qqueue_history to `{$db['username']}`@`{$host}`;";
                    $output[$host][] = "GRANT SELECT ON `mysql`.qqueue_jobs to `{$db['username']}`@`{$host}`;";
                    $output[$host][] = "GRANT SELECT ON `mysql`.qqueue_queues to `{$db['username']}`@`{$host}`;";
                    $output[$host][] = "GRANT SELECT ON `mysql`.qqueue_usrGrps to `{$db['username']}`@`{$host}`;";
                }
            }
            for ($i = 0; $i < count($output[$db['hosts'][0]]); $i++) {
                foreach ($db['hosts'] as $host) {
                    echo $output[$host][$i] . PHP_EOL;
                }
            }
            echo PHP_EOL;
        }
        echo 'FLUSH PRIVILEGES;' . PHP_EOL;
    }

    /**
     * Displays the virtual host configuration.
     */
    private function _vhost() {
        // guess an alias name
        $alias = basename($this->application_path);

        echo <<<EOT
Virtual host configuration:
    
    #SetEnv APPLICATION_ENV development
    
    DocumentRoot "{$this->application_path}/public"
    # or Alias /{$alias} "{$this->application_path}/public"
    <Directory "{$this->application_path}/public">
        Options FollowSymLinks -Indexes -MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

Uncomment 'SetEnv APPLICATION_ENV development' for a debugging.

EOT;
    }

    /**
     * Drops the databases including the user databases.
     */
    private function _drop() {
        echo "Dropping databases." . PHP_EOL;

        if (isset($this->options['database']['web'])) {
            $webDb = $this->options['database']['web'];
            echo '    Dropping ' . $webDb['dbname'] . '.' . PHP_EOL;

            // drop web application database
            $sql1 = "DROP DATABASE IF EXISTS {$webDb['dbname']};";
            exec($this->_getConnectionString($webDb) . " -e'{$sql1}'");
        }

        if (isset($this->options['database']['user'])) {
            $userDb = $this->options['database']['user'];
            echo '    Dropping ' . $userDb['dbname'] . '.' . PHP_EOL;

            // drop user databases
            $dbname = str_replace('%', '', $userDb['dbname']);
            exec($this->_getConnectionString($userDb) . " -e 'show databases' -s | egrep '^{$dbname}' | xargs -I '@@' " . $this->_getConnectionString($userDb) . "-e 'DROP DATABASE `@@`'");
        }
    }

    /**
     * Creates the databases and tables as long as they do not exist yet.
     */
    private function _sync() {
        echo "Syncing databases." . PHP_EOL;

        // set up web database
        if (isset($this->options['database']['web'])) {
            $webDb = $this->options['database']['web'];
            echo '    Syncing ' . $webDb['dbname'] . '.' . PHP_EOL;

            // create web application database if it does not exist
            $sql1 = "CREATE DATABASE IF NOT EXISTS {$webDb['dbname']};";
            exec($this->_getConnectionString($webDb) . " -e'{$sql1}'");

            // source schema sql scripts for all active modules
            $moduleDir = __DIR__ . '/../../modules';
            foreach ($this->options['modules'] as $module) {
                $schema = $moduleDir . '/' . $module . '/db/schema.sql';
                if (file_exists($schema)) {
                    exec($this->_getConnectionString($webDb) . " -D'{$webDb['dbname']}' < {$schema}");
                }
                $data = $moduleDir . '/' . $module . '/db/data.sql';
                if (file_exists($data)) {
                    exec($this->_getConnectionString($webDb) . " -D'{$webDb['dbname']}' < {$data}");
                }
            }
        }

        // set up guest user database
        if (isset($this->options['database']['user'])) {
            $userDb = $this->options['database']['user'];
            echo '    Syncing ' . $userDb['dbname'] . '.' . PHP_EOL;

            // create guest database
            $dbname = str_replace('%', 'guest', $userDb['dbname']);
            exec($this->_getConnectionString($userDb) . " -e 'CREATE DATABASE IF NOT EXISTS {$dbname};'");
        }
    }

    /**
     * Runs the database initalisation process.
     */
    private function _init() {
        echo "Running init process." . PHP_EOL;

        foreach ($this->models as $model) {
            $model->init($this->options);
        }

        echo '    done!' . PHP_EOL;
    }

    /**
     * Builds the string needed for accessing the mysql server
     * @return string database connection string
     */
    private function _getConnectionString($db) {
        $conn = "";

        // set the correct binary
        if (isset($db['mysql'])) {
            $conn = "{$db['mysql']} ";
        } else {
            $conn = "mysql ";
        }

        // set the correct conection (host/port or socket)
        if (isset($db['socket']) && $db['socket'] !== '') {
            $conn = $conn . "-S{$db['socket']} ";
        } else {
            $conn = $conn . "-h{$db['host']} ";
            if (isset($db['port']) && $db['port'] !== "") {
                $conn = $conn . "-P{$db['port']} ";
            }
        }

        // set user and password and return
        $conn = $conn . "-u{$db['username']} -p{$db['password']} ";

        return $conn;
    }

    /**
     * Displays an error and quits the script.
     * @param string $error the error string
     */
    protected function _error($error) {
        echo $error . PHP_EOL;
        die(0);
    }

}

