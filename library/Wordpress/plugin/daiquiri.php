<?php
/*
  Plugin Name: Daiquiri
  Description: Daiquiri framework integration. After activation configure the database connection or you will not be able to log in again.  
  Author: Jochen S. Klar, and AIP E-Science
  Version: 1.0
  Text Domain: Daiquiri framework integration. 
 */

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

/*
 * A global array to hold the options and their default values.
 */
global $daiquiri_options;
$daiquiri_options = array(
    'daiquiri_url' => 'http://localhost/',
    'daiquiri_db_host' => 'localhost',
    'daiquiri_db_port' => '3306',
    'daiquiri_db_dbname' => 'daiquiri_web'
);

/*
 * Add the options when the plugin is activated.
 */

register_activation_hook(WP_PLUGIN_DIR . '/daiquiri/daiquiri.php', 'daiquiri_activate');

function daiquiri_activate() {
    global $daiquiri_options;
    foreach ($daiquiri_options as $option => $default) {
        add_option($option, $default);
    }
}

/*
 * Register the settings when the admin interface is loaded.
 */

add_action('admin_init', 'daiquiri_admin_init');

function daiquiri_admin_init() {
    global $daiquiri_options;
    foreach (array_keys($daiquiri_options) as $option) {
        register_setting('daiquiri', $option);
    }
}

/*
 * Initialize the option page in the administration interface.
 */

add_action('admin_menu', 'daiquiri_admin_menu');

function daiquiri_admin_menu() {
    add_options_page('Daiquiri Administration', 'Daiquiri', 'activate_plugins', 'daiquiri', 'daiquiri_admin_display');
    remove_menu_page('users.php');
}

function daiquiri_admin_display() {
    global $daiquiri_options;
    ?>
    <div class="wrap">
        <h2>Daiquiri Administration</h2>
        <p style="color: #b94a48;">
            <strong>Important:</strong> Adjust at least the daiquiri_db_dbname field.
        </p>
        <form method="post" action="options.php">
            <table class="form-table">
                <?php settings_fields('daiquiri'); ?>
                <?php
                foreach (array_keys($daiquiri_options) as $option) :
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php echo $option ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo $option ?>" value="<?php echo get_option($option); ?>" />
                        </td>
                    </tr>
                <?php endforeach ?>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" value="Save changes" />
            </p>
        </form>
        <p style="color: #b94a48;">
            Please ensure that the user configured in wp-config.php has SELECT permissions on
            the database.
            Otherwise you will not able to log in with the daiquiri credentials.
        </p>   
        <p>
            You can archive this with SQL command: 
            <code>GRANT SELECT ON `<?php echo get_option('daiquiri_db_dbname') ?>`.* to 'USER'@'localhost';</code>
        </p>
        <p>
            On a different machine you need to create the user first: 
            <code>CREATE USER 'USERNAME'@'localhost' IDENTIFIED BY 'PASSWORD';</code>
        </p>
    </div>
    <?php
}

/*
 * Override the build in authentification of wordpress
 */

add_action('wp_authenticate', 'daiquiri_authenticate', 1, 2);

function daiquiri_authenticate($username, $password) {
    require_once('./wp-includes/registration.php');

    if (!empty($username) && !empty($password)) {
        $c = 'mysql:';
        $c .= 'host=' . get_option('daiquiri_db_host') . ';';
        $c .= 'port=' . get_option('daiquiri_db_port') . ';';
        $c .= 'dbname=' . get_option('daiquiri_db_dbname') . ';';

        $adapter = new PDO($c, DB_USER, DB_PASSWORD);
        $stmt = $adapter->prepare("SELECT `u`.`id`,`u`.`username`,`u`.`email`,`u`.`password`,`r`.`role` FROM `Auth_User` as `u`,`Auth_Status` as `s`,`Auth_Roles` as `r` WHERE `u`.`username` = ? AND `u`.`status_id` = `s`.`id` AND `u`.`role_id` = `r`.`id` AND `s`.`status` = 'active';");
        $stmt->execute(array($username));
        $row = $stmt->fetch();

        if ($row) {
            $ex = explode('$', $row['password']);
            $algo = $ex[1];
            $salt = $ex[2];

            $hash = crypt($password, '$' . $algo . '$' . $salt . '$');

            if ($hash === $row['password']) {
                $user = array(
                    'display_name' => $username,
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $row['email']
                );

                // get the role of the user
                if ($row['role'] === 'admin') {
                    $user['role'] = 'administrator';
                } else if ($row['role'] === 'manager') {
                    $user['role'] = 'editor';
                } else {
                    $user['role'] = 'subscriber';
                }

                // get the users details
                $details = array(
                    'firstname' => 'first_name',
                    'lastname' => 'last_name',
                    'website' => 'user_url'
                );
                $stmt = $adapter->prepare("SELECT `key`,`value` FROM `Auth_Details` WHERE `user_id` = ?;");
                $stmt->execute(array($row['id']));
                while ($row = $stmt->fetch()) {
                    if (array_key_exists($row['key'], $details)) {
                        $user[$details[$row['key']]] = $row['value'];
                    }
                }
                if (isset($user['first_name']) && isset($user['last_name'])) {
                    $user['display_name'] = $user['first_name'] . ' ' . $user['last_name'];
                }

                // update or create the user in the wordpress db
                if ($id = username_exists($username)) {
                    // update the user in the wordpress database
                    $user['ID'] = $id;
                    wp_update_user($user);
                } else {
                    wp_insert_user($user);
                }
            } else {
                global $ext_error;
                $ext_error = "wrongpw";
                $username = NULL;
            }
        } else {
            global $ext_error;
            $ext_error = "notindb";
            $username = NULL;
        }
    }
}

/*
 * Override the message for the login window.
 */

add_filter('login_errors', 'daiquiri_login_errors');

function daiquiri_login_errors() {
    global $error;
    global $ext_error;
    if ($ext_error == "notindb")
        return "<strong>ERROR:</strong> Username not found.";
    else if ($ext_error == "wrongrole")
        return "<strong>ERROR:</strong> You don't have permissions to log in.";
    else if ($ext_error == "wrongpw")
        return "<strong>ERROR:</strong> Invalid password.";
    else if ($ext_error == "wrongpw")
        return "<strong>ERROR:</strong> Wrong Configuration.";
    else
        return $error;
}

/*
 * Disable the user registration and password retrieval functions
 */

add_action('lost_password', 'disable_function');
add_action('user_register', 'disable_function');
add_action('password_reset', 'disable_function');

function disable_function() {
    $errors = new WP_Error();
    $errors->add('registerdisabled', __('User registration is not available from this site, so you can\'t create an account or retrieve your password from here.'));
    login_header(__('Log In'), '', $errors);
    ?>
    <p id="backtoblog"><a href="<?php bloginfo('url'); ?>/" title="<?php _e('Are you lost?') ?>"><?php printf(__('&larr; Back to %s'), get_bloginfo('title', 'display')); ?></a></p>
    <?php
    exit();
}

/*
 * Hide the personal profile options.
 */

add_action('profile_personal_options', 'daiquiri_hide_start');

function daiquiri_hide_start() {
    echo '<div style="visibility: hidden;"><!-- the following fields are hidden since a change to these values would be overwritten at the next login. -->';
}

add_action('show_user_profile', 'daiquiri_hide_end');

function daiquiri_hide_end() {
    echo '</div><!-- hidden -->';
}

/*
 * 
 */

add_action('wp_logout', 'daiquiri_logout');

function daiquiri_logout() {
    require_once('HTTP/Request2.php');
    $req = new HTTP_Request2(get_option('daiquiri_url') . '/auth/login/logout/cms/false');
    var_dump();
    $req->setMethod('GET');
    $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
    $response = $req->send();
}