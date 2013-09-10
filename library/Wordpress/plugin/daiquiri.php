<?php
/*
  Plugin Name: Daiquiri framework integration
  Description: Daiquiri framework integration
  Author: author
  Version: 1.0
  Text Domain: Daiquiri framework integration
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
 * Add the options when the plugin is activated.
 */

register_activation_hook(WP_PLUGIN_DIR . '/daiquiri/daiquiri.php', 'daiquiri_activate');

function daiquiri_activate() {
    add_option('daiquiri_url', 'http://localhost/');
}

/*
 * Register the settings when the admin interface is loaded.
 */

add_action('admin_init', 'daiquiri_admin_init');

function daiquiri_admin_init() {
    register_setting('daiquiri', 'daiquiri_url');
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
    ?>
    <div class="wrap">
        <form method="post" action="options.php">
            <table class="form-table">
                <?php settings_fields('daiquiri'); ?>
                <tr valign="top">
                    <th scope="row">
                        <label>daiquiri_url</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="daiquiri_url" value="<?php echo get_option('daiquiri_url'); ?>" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" value="Save changes" />
            </p>
        </form>
    </div>
    <?php
}

/*
 * Automatiacally login the user which is logged in into daiquiri right now.
 */ 

add_action('init', 'daiquiri_auto_login');

function daiquiri_auto_login() 
{
    // check which user is logged in into daiquiri right now
    $siteUrl = get_option('siteurl');
    $layoutUrl = get_option('daiquiri_url') . '/auth/account/show/';
    if (strpos($layoutUrl, $siteUrl) !== false) {
        echo '<h1>Error with theme</h1><p>Layout URL is below CMS URL.</p>';
        die(0);
    }

    // construct request
    require_once('HTTP/Request2.php');
    $req = new HTTP_Request2($layoutUrl);
    $req->setConfig(array(
        'connect_timeout' => 2,
        'timeout' => 3
    ));
    $req->setMethod('GET');
    $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
    $req->setHeader('Accept: application/json');

    try {
        $response = $req->send();
        $status = $response->getStatus();
        $body = $response->getBody();
    } catch (HTTP_Request2_Exception $e) {
        echo '<h1>Error with daiquiri auth</h1><p>Error with HTTP request.</p>';
        die(0);
    }

    if ($status == 403) {
        if (is_user_logged_in()) {
            wp_clear_auth_cookie();
            wp_redirect($_SERVER['REQUEST_URI']);
            exit();
        }
    } else if ($status == 200) {
        // decode the non empty json to the remote user array
        $remoteUser = json_decode($response->getBody());

        $daiquiriUser = array();
        foreach(array('id','username','firstname','lastname','email','website','role') as $key) {
            if (isset($remoteUser->data->$key)) {
                $daiquiriUser[$key] = $remoteUser->data->$key;
            }
        }

        if (is_user_logged_in()) {
            // check if the RIGHT user is logged in
            $currentUser = wp_get_current_user();;
            if (strcmp($currentUser->user_login, $daiquiriUser['id']) != 0) {
                wp_clear_auth_cookie();
                wp_redirect($_SERVER['REQUEST_URI']);
                exit();
            }

            // check if the user was updated
            $updated = false;

            // check main credentials
            foreach(array(
                    'username' => 'user_nicename',
                    'email' => 'user_email',
                    'firstname' => 'first_name',
                    'lastname' => 'last_name'
                ) as $key => $value) {

                if (isset($daiquiriUser[$key])) {
                    if (strcmp($currentUser->$value, $daiquiriUser[$key]) != 0) {
                        $updated = true;
                    }
                }

            }

            // check url
            $currentUrl  = str_replace(array('http://','https://'),'', $currentUser->user_url);
            $daiquiriUrl = str_replace(array('http://','https://'),'', $daiquiriUser['website']);
            if (strcmp($currentUrl,$daiquiriUrl) != 0) {
                $updated = true;
            }

            // check role
            if (count($currentUser->roles) != 1) {
                $updated = true;
            } else {
                if ($daiquiriUser['role'] === 'admin') {
                    if ($currentUser->roles[0] !== 'administrator') {
                        $updated = true;
                    }
                } else if ($daiquiriUser['role'] === 'manager') {
                    if ($currentUser->roles[0] !== 'editor') {
                        $updated = true;
                    }
                } else {
                    if ($currentUser->roles[0] !== 'subscriber') {
                        $updated = true;
                    }
                }
            }
            
            // update the user if things were changed
            if ($updated === true) {
                // logout and redirect
                wp_clear_auth_cookie();
                wp_redirect($_SERVER['REQUEST_URI']);
                exit();
            }
        } else {
            // create/update the wordpress user to match the daiquiri user
            // the id in daiquiri maps to the user_login in wp
            // the username in daiquiri maps to the user_nicename in wp

            $wpUser = array(
                'user_login' => $daiquiriUser['id'],
                'user_nicename' => $daiquiriUser['username'],
                'user_pass' => 'foo',
                'user_email' => $daiquiriUser['email']
            );

            // get the role of the user
            if ($daiquiriUser['role'] === 'admin') {
                $wpUser['role'] = 'administrator';
            } else if ($daiquiriUser['role'] === 'manager') {
                $wpUser['role'] = 'editor';
            } else {
                $wpUser['role'] = 'subscriber';
            }

            if (isset($daiquiriUser['firstname'])) {
                $wpUser['first_name'] = $daiquiriUser['firstname'];
            }
            if (isset($daiquiriUser['lastname'])) {
                $wpUser['last_name'] = $daiquiriUser['lastname'];
            }
            if (isset($daiquiriUser['website'])) {
                $wpUser['user_url'] = $daiquiriUser['website'];
            }
            if (isset($wpUser['first_name']) && isset($wpUser['last_name'])) {
                $wpUser['display_name'] = $wpUser['first_name'] . ' ' . $wpUser['last_name'];
            }

            // update or create the user in the wordpress db
            $storedUser = get_user_by('login', $wpUser['user_login']);
            if ($storedUser === false) {
                // create a new user in the wordpress db
                $status = wp_insert_user($wpUser);
            } else {
                // update the user in the wordpress database
                $wpUser['ID'] = $storedUser->ID;
                $status = wp_update_user($wpUser);
            }

            if (is_int($status)) {
                $userId = $status;
            } else {
                var_dump($status);
            }

            // log in the newly created or updated user
            $user = get_userdata($userId);
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login);
        }
    } else {
        echo '<h1>Error with auth</h1><p>HTTP request status != 200.</p>';
        die(0); 
    }
}

/*
 * Override the build in authentification of wordpress
 */

add_action('wp_authenticate', 'daiquiri_authenticate', 1, 2);

function daiquiri_authenticate($username, $password) {
    require_once('./wp-includes/registration.php');

    if (!is_user_logged_in()) {
        if ($_GET["no_redirect"] !== 'true') {
            $daiquiriLogin = get_option('daiquiri_url') . 'auth/login';
            wp_redirect($daiquiriLogin);
            exit;
        }
    } else {
        // check if there is a redirect
        if (empty($_GET['redirect_to'])) {
            // redirect to the daiquiri login page
            $daiquiriLogin = get_option('daiquiri_url') . 'auth/login';
            wp_redirect($daiquiriLogin);
            exit;
        } else {
            // just do the redirect
            wp_redirect($_GET['redirect_to']);
            exit();
        }
    }
}

/*
 * Disable the user registration and password retrieval functions
 */

/*
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
*/
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
 * Log out of daiquiri when logging out of wordpress.
 */

add_action('wp_logout', 'daiquiri_logout');

function daiquiri_logout() {
    require_once('HTTP/Request2.php');
    $req = new HTTP_Request2(get_option('daiquiri_url') . '/auth/login/logout?cms_logout=false');
    $req->setMethod('GET');
    $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
    $response = $req->send();
}