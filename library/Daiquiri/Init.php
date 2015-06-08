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
        'core' => array(),
        'auth' => array('core'),
        'contact' => array('core'),
        'data' => array('auth','core'),
        'meetings' => array('auth','core'),
        'query' => array('data','auth','core'),
        'uws' => array('core')
    );

    protected static $_commandline_options = array(
        'h|help' => 'Displays usage information.',
        'a|application' => 'Creates the application.ini file (must be invoked first).',
        'l|links' => 'Creates the neccessary softlinks.',
        'm|minify' => 'Minifies the static js and css files.',
        'u|user' => 'Displays the commands to create the database user.',
        'c|clean' => 'Displays the commands to clean the database.',
        'v|vhost' => 'Displays the virtual host configuration.',
        'w|wordpress' => 'Displays the wp-config.php entries.',
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
        $this->application_path = $application_path;
        $this->daiquiri_path = $daiquiri_path;
        $this->input = $input;

        // put Zend in the include_path
        set_include_path(implode(PATH_SEPARATOR, array(
            realpath($this->daiquiri_path . '/library'),
            get_include_path(),
        )));

        // setup autoloader
        require_once('Zend/Loader/Autoloader.php');
        Zend_Loader_Autoloader::getInstance();

        // parse command line
        $this->_parseCommandLine();

        // init the options array with database and mail options
        $this->options = array();
        $this->_processDatabaseOptions();
        $this->_processMailOptions();
        $this->_processModulesOptions();

        // setup zend application environment
        $this->_setupEnvironment();

        // get init models from the modules
        foreach(array_keys(Daiquiri_Init::$_modules) as $module) {
            $classname = ucfirst($module) . '_Model_Init';
            $this->models[$module] = new $classname($this);
        }

        // parse the config array for each model
        $this->options['config'] = array();
        foreach($this->options['modules'] as $module) {
            $model = $this->models[$module];
            $model->processConfig();
        }

        // update config singleton
        Daiquiri_Config::getInstance()->setConfig($this->options['config']);

        // parse the init array for each model
        $this->options['init'] = array();
        foreach($this->options['modules'] as $module) {
            $model = $this->models[$module];
            $model->processInit();
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

        $this->options['application'] = array(
            'phpSettings' => array(
                'display_startup_errors' => 1,
                'display_errors' => 1
            ),
            'bootstrap' => array(
                'path' => $this->application_path . '/application/Bootstrap.php',
                'class' => 'Bootstrap'
            ),
            'appnamespace' => 'Application',
            'autoloadernamespaces' => array('Daiquiri'),
            'resources' => array(
                'frontController' => array(
                    'params' => array(
                        'displayExceptions' => 1
                    ),
                    'controllerDirectory' => $this->application_path . '/application/controllers',
                    'moduleDirectory' => $this->daiquiri_path . '/modules'
                ),
                'modules' => $this->options['modules'],
                'multidb' => array(
                    'web' => array(
                        'adapter' => 'Pdo_Mysql',
                        'charset' => 'utf8',
                        'default' => 'true',
                    ),
                    'user' => array(
                        'adapter' => 'Pdo_Mysql',
                        'charset' => 'utf8',
                    )
                )
            )
        );
        foreach (array('web','user') as $adapter) {
            if (isset($this->options['database'][$adapter])) {
                $database = $this->options['database'][$adapter];

                foreach (array('dbname', 'username', 'password', 'host') as $key) {
                    $this->options['application']['resources']['multidb'][$adapter][$key] = $database[$key];
                }
                if ($database['host'] !== 'localhost') {
                    $this->options['application']['resources']['multidb'][$adapter]['port'] = $database['port'];
                }
            } else {
                unset($this->options['application']['resources']['multidb'][$adapter]);
            }
        }

        // initialize Zend_Application and bootstrap
        $application = new Zend_Application(APPLICATION_ENV, $this->options['application']);
        $front = $application->getBootstrap()
                ->bootstrap('frontController')
                ->getResource('frontController');

        // fake request
        $request = new Daiquiri_Controller_Request_Init();
        $front->setRequest($request);

        $application->bootstrap();
    }

    /**
     * Processes the 'database' part of $options.
     */
    private function _processDatabaseOptions() {
        if (!isset($this->input['database'])) {
            $this->_error("No database options provided.");
        } else if (!is_array($this->input['database'])) {
            $this->_error('Database options need to be an array.');
        } else {
            $input = $this->input['database'];
        }

        $database_defaults = array(
            'host' => null,
            'dbname' => null,
            'username' => null,
            'password' => null,
            'port' => 3306,
            'mysql' => '/usr/bin/mysql',
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
            'autoloadernamespaces[] = "Daiquiri"',
            '',
            'resources.frontController.params.displayExceptions = 0',
            'resources.frontController.controllerDirectory = APPLICATION_PATH "/controllers"',
            'resources.frontController.moduleDirectory = APPLICATION_PATH "/../modules"',
            'resources.view[] = ',
            'resources.view.helperPath.Daiquiri_View_Helper = APPLICATION_PATH "/../library/Daiquiri/View/Helper"',
            'resources.layout.layoutPath = APPLICATION_PATH "/layouts/scripts/"',
            ''
        );

        // prepare module part of application.ini
        foreach ($this->options['modules'] as $module) {
            $output[] = "resources.modules[] = '{$module}'";
        }
        $output[] = '';

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
        $links = array(
            $this->application_path . '/library' => $this->daiquiri_path . '/library',
            $this->application_path . '/modules' => $this->daiquiri_path . '/modules',
            $this->application_path . '/public/captcha' => $this->options['config']['core']['captcha']['dir'],
            $this->application_path . '/public/daiquiri' => $this->daiquiri_path . '/client'
        );

        // cms (word press directory)
        $cms = $this->application_path . '/public' . $this->options['config']['core']['cms']['url'];
        if (!empty($this->options['config']['core']['cms']) && $this->options['config']['core']['cms']['enabled']) {
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
        system("rm -rf public/min");
        mkdir('public/min',0755,true);
        mkdir('public/min/js',0755,true);
        mkdir('public/min/css',0755,true);
        mkdir('public/min/fonts',0755,true);

        echo "minifing js and css files." . PHP_EOL;

        exec("echo '/* Automatically created file. Manual customization is not recommended. */' > public/min/js/daiquiri.js" );
        exec("echo '/* Automatically created file. Manual customization is not recommended. */' > public/min/css/daiquiri.css");

        // get the layout file and parse out the call to the daiquiri view helper
        $layoutFile = $this->application_path . '/application/layouts/scripts/layout.phtml';
        $html = file_get_contents($layoutFile);
        $html = trim(preg_replace('/\s\s+/',' ', $html)); // remove newlines
        $pattern = '/' . preg_quote('$this->headStatic') . '\s*\(\s*(array\([^?]*?\))\s*' . preg_quote(',') . '\s*(array\([^?]*?\))\s*' . preg_quote(');') . '/';
        if (preg_match($pattern,$html,$matches)) {
            eval('$overrideFiles = ' . $matches[2] . ';');
        }

        // if the overrideFiles variable is set merge these variabes with the default ones
        if (isset($overrideFiles)) {
            $files = array_merge(Daiquiri_View_Helper_HeadStatic::$files, $overrideFiles);
        } else {
            $files = Daiquiri_View_Helper_HeadStatic::$files;
        }

        // collect files
        $js = array();
        $css = array();
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'js') {
                $js[] = $this->application_path . "/public/" . $file;
            } else if ($ext === 'css') {
                $css[] = $this->application_path . "/public/" . $file;
            }
        }

        // minify files
        switch ($this->options['config']['core']['minify']['method']) {
            case 'yui':
                foreach($js as $file) {
                    exec("yui-compressor " . $file . " >> public/min/js/daiquiri.js");
                }
                foreach($css as $file) {
                    exec("yui-compressor " . $file . " >> public/min/css/daiquiri.css");
                }
                break;
            case 'uglify':
                exec("uglifyjs " . implode(' ',$js) . " --compress --mangle >> public/min/js/daiquiri.js");
                exec("uglifycss --ugly-comments " . implode(' ',$css) . " >> public/min/css/daiquiri.css");
                break;
            default:
                $this->_error("Unknown value in \$options['config']['core']['minify']['method'].");
        }

        // take care of images
        foreach (Daiquiri_View_Helper_HeadStatic::$links as $key => $value) {
            $target = $this->application_path . '/public/' . $value;
            $link = $this->application_path . '/public/min/' . $key;
            if (is_link($link)) {
                unlink($link);
            }
            if (!file_exists($target)) {
                echo 'Error: ' . $target . ' does not exist.' . PHP_EOL;
                die(0);
            } else {
                echo "creating symlink " . $target . ' -> ' . $link . PHP_EOL;
                if (!file_exists(dirname($link))) {
                    mkdir(dirname($link),0755,true);
                }
                symlink($target, $link);
            }
        }
    }

    /**
     * Displays the commands to delete the database.
     */
    private function _clean() {
        foreach ($this->options['database'] as $db) {
            if (!in_array($db['host'], array('localhost','127.0.0.1','::1'))) {
                $host  = trim(`ip addr | awk '/inet/ && /eth0/{sub(/\/.*$/,"",$2); print $2}'`);
            } else {
                $host = $db['host'];
            }

            echo <<<EOT

-- on {$db['host']}

DELETE FROM mysql.user where User='{$db['username']}' AND Host='{$host}';
DELETE FROM mysql.tables_priv where User='{$db['username']}' AND Host='{$host}';
DELETE FROM mysql.db where User='{$db['username']}' AND Host='{$host}';
FLUSH PRIVILEGES;

EOT;
        }
        echo PHP_EOL;
    }

    /**
     * Displays the commands to create the database user.
     */
    private function _user() {
        if (isset($this->options['database']['web'])) {
            $db = $this->options['database']['web'];

            if (!in_array($db['host'], array('localhost','127.0.0.1','::1'))) {
                $host  = trim(`ip addr | awk '/inet/ && /eth0/{sub(/\/.*$/,"",$2); print $2}'`);
            } else {
                $host = $db['host'];
            }

            echo <<<EOT

-- on {$db['host']}

CREATE USER '{$db['username']}'@'{$host}' IDENTIFIED BY '{$db['password']}';
GRANT ALL PRIVILEGES ON `{$db['dbname']}`.* to '{$db['username']}'@'{$host}';
FLUSH PRIVILEGES;

EOT;
        }

        if (isset($this->options['database']['user'])) {
            $db = $this->options['database']['user'];

            if (!in_array($db['host'], array('localhost','127.0.0.1','::1'))) {
                $host  = trim(`ip addr | awk '/inet/ && /eth0/{sub(/\/.*$/,"",$2); print $2}'`);
            } else {
                $host = $db['host'];
            }

            $alter = empty($this->options['config']['data']['writeToDB']) ? '' : ', ALTER';

            echo <<<EOT

-- on {$db['host']}

CREATE USER '{$db['username']}'@'{$host}' IDENTIFIED BY '{$db['password']}';
GRANT ALL PRIVILEGES ON `{$db['dbname']}`.* to '{$db['username']}'@'{$host}';
GRANT SELECT ON `mysql`.`func` to '{$db['username']}'@'{$host}';

EOT;

            if ($this->options['config']['query']['query']['type'] === 'qqueue') {
                echo <<<EOT
GRANT SELECT ON `mysql`.`qqueue_queues` to '{$db['username']}'@'{$host}';
GRANT SELECT ON `mysql`.`qqueue_usrGrps` to '{$db['username']}'@'{$host}';
GRANT SELECT ON `mysql`.`qqueue_jobs` to '{$db['username']}'@'{$host}';
GRANT SELECT, UPDATE ON `mysql`.`qqueue_history` to '{$db['username']}'@'{$host}';

EOT;
            }

            if ($this->options['config']['query']['processor']['type'] === 'paqu') {
                echo <<<EOT

-- on {$db['host']} for the scratch db
GRANT ALL PRIVILEGES ON `{$this->options['config']['query']['scratchdb']}`.* to '{$db['username']}'@'{$host}';

EOT;
            }

                echo <<<EOT

-- on {$db['host']} for every science database
GRANT SELECT{$alter} ON `SCIENCE_DATABASE`.* to '{$db['username']}'@'{$host}';


EOT;
        }
    }

    /**
     * Displays the virtual host configuration.
     */
    private function _vhost() {
        echo "    #SetEnv APPLICATION_ENV development\n\n";
        echo "    XSendFile on\n";

        if (isset($this->options['config']['query'])
            && isset($this->options['config']['query']['download'])
            && isset($this->options['config']['query']['download']['dir'])) {

            echo "    XSendFilePath {$this->options['config']['query']['download']['dir']}\n";
        }
        if (isset($this->options['config']['data'])
            && isset($this->options['config']['data']['files'])
            && isset($this->options['config']['data']['files']['static'])) {

            foreach($this->options['config']['data']['files']['static'] as $path) {
                echo "    XSendFilePath {$path}\n";
            }
        }
        if (isset($this->options['init']['data'])
            && isset($this->options['init']['data']['static'])) {

            foreach($this->options['init']['data']['static'] as $static) {
                echo "    XSendFilePath {$static['path']}\n";
            }
        }

        echo <<<EOT

    DocumentRoot "{$this->application_path}/public"
    <Directory "{$this->application_path}/public">
        Options FollowSymLinks -Indexes -MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

EOT;
    }

    /**
     * Displays the virtual host configuration.
     */
    private function _wordpress() {
        echo <<<EOT
\$_SERVER['HTTPS']='on';    // for ssl

define('DAIQUIRI_DB','');  // put the daiquiri web database here (for shortcodes)
define('DAIQUIRI_URL',''); // put the daiquiri url here
define('DAIQUIRI_NAVIGATION_PATH','{$this->options['config']['core']['cms']['navPath']}');

define('COOKIEPATH','/');
define('SITECOOKIEPATH',COOKIEPATH);
define('ADMIN_COOKIE_PATH',COOKIEPATH);
define('PLUGINS_COOKIE_PATH',COOKIEPATH);

EOT;
    }

    /**
     * Drops the databases including the user databases.
     */
    private function _drop() {
        // confirm drop
        printf("Dropping databases. Please type 'yes' to confirm: ");
        $fp = fopen("php://stdin","r");
        if (trim(fgets($fp)) !== 'yes') {
            echo "exiting." . PHP_EOL;;
            die(0);
        }

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

        foreach ($this->options['modules'] as $module) {
            $model = $this->models[$module];
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

