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

 /*
 * Add the options when the plugin is activated.
 */

function daiquiri_activate() {
    add_option('daiquiri_url', 'http://localhost/');
    add_option('daiquiri_navigation_path', '/var/lib/daiquiri/navigation');
}

register_activation_hook(WP_PLUGIN_DIR . '/daiquiri/daiquiri.php', 'daiquiri_activate');

/*
 * Register the settings when the admin interface is loaded.
 */

function daiquiri_admin_init() {
    register_setting('daiquiri', 'daiquiri_url');
    register_setting('daiquiri', 'daiquiri_navigation_path');
}

add_action('admin_init', 'daiquiri_admin_init');

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
                <tr valign="top">
                    <th scope="row">
                        <label>daiquiri_navigation_path</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="daiquiri_navigation_path" value="<?php echo get_option('daiquiri_navigation_path'); ?>" />
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