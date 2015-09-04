<?php
/*
  Plugin Name: Daiquiri framework integration
  Description: Daiquiri framework integration
  Author: author
  Version: 1.0
  Text Domain: Daiquiri framework integration
 */

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

// Include daiquiri shortcodes
require_once('daiquiri_shortcodes.php');
require_once('daiquiri_navigation.php');

/* disable admin bar */

$show_admin_bar = false;

/*
 * Automatiacally login the user which is logged in into daiquiri right now.
 */

add_action('init', 'daiquiri_auto_login');

function daiquiri_auto_login()
{
    if (!is_user_logged_in()) {
        // check which user is logged in into daiquiri right now
        $userUrl = DAIQUIRI_URL . '/auth/account/show/';

        require_once('HTTP/Request2.php');
        $req = new HTTP_Request2($userUrl);
        $req->setConfig(array(
            'ssl_verify_peer' => false, // we trust the certificate here
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
            foreach(array('id','username','email','role') as $key) {
                if (isset($remoteUser->row->$key)) {
                    $daiquiriUser[$key] = $remoteUser->row->$key;
                }
            }
            foreach(array('firstname','lastname','website') as $key) {
                if (isset($remoteUser->row->details->$key)) {
                    $daiquiriUser[$key] = $remoteUser->row->details->$key;
                }
            }

            // create/update the wordpress user to match the daiquiri user
            // the id in daiquiri maps to the user_login in wp
            // the username in daiquiri maps to the user_nicename in wp

            $wpUser = array(
                'user_login' => $daiquiriUser['id'],
                'user_nicename' => $daiquiriUser['username'],
                'user_email' => $daiquiriUser['email']
            );

            // get the role of the user
            if ($daiquiriUser['role'] === 'admin') {
                $wpUser['role'] = 'administrator';
            } else if ($daiquiriUser['role'] === 'manager') {
                $wpUser['role'] = 'editor';
            } else if (defined('DAIQUIRI_AUTHOR_ROLE') && $daiquiriUser['role'] === DAIQUIRI_AUTHOR_ROLE) {
                $wpUser['role'] = 'author';
            } else if (defined('DAIQUIRI_CONTRIBUTOR_ROLE') && $daiquiriUser['role'] === DAIQUIRI_CONTRIBUTOR_ROLE) {
                $wpUser['role'] = 'contributor';
            } else {
                $wpUser['role'] = 'subscriber';
            }

            // get the name and the other credentials
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
                // fake a random password
                $wpUser['user_pass'] = uniqid();

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
                echo '<h1>Error with auth</h1>';
                var_dump($status);
                exit();
            }

            // log in the newly created or updated user
            $user = get_userdata($userId);
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login);
        } else {
            echo '<h1>Error with auth</h1><p>HTTP request status != 200.</p>';
            die(0);
        }
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
            wp_redirect(get_site_url() . '/../auth/login');
            exit;
        }
    } else {
        // check if there is a redirect
        if (empty($_GET['redirect_to'])) {
            wp_redirect(get_site_url() . '/../auth/login');
            exit;
        } else {
            // just do the redirect
            wp_redirect($_GET['redirect_to']);
            exit();
        }
    }
}

/*
 * Hide the personal profile options.
 */

add_action('profile_personal_options', 'daiquiri_hide_start');

function daiquiri_hide_start() {
    echo '<div style="display: none;"><!-- the following fields are hidden since a change to these values would be overwritten at the next login. -->';
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
    wp_redirect(get_site_url() . '/../auth/login/logout?cms_logout=false');
    exit();
}