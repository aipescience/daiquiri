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