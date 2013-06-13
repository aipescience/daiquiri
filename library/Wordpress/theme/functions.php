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
 * A global array to hold the options and their default values.
 * Add the options when the theme is activated.
 */
global $daiquiri_theme_options;
$daiquiri_theme_options = array(
    'daiquiri_layout_url' => 'http://localhost/layout'
);
foreach ($daiquiri_theme_options as $option => $default) {
    add_option($option, $default);
}

/*
 * Register the settings when the admin interface is loaded.
 */

add_action('admin_init', 'daiquiri_theme_init');

function daiquiri_theme_init() {
    global $daiquiri_theme_options;
    foreach (array_keys($daiquiri_theme_options) as $option) {
        register_setting('daiquiri_theme', $option);
    }
}

/*
 * Initialize the option page in the administration interface.
 */

add_action('admin_menu', 'daiquiri_theme_menu');

function daiquiri_theme_menu() {
    add_theme_page('Daiquiri Theme Options', 'Options', 'edit_theme_options', 'daiquiri-theme-options', 'daiquiri_theme_options');
}

function daiquiri_theme_options() {
    global $daiquiri_theme_options;
    ?>
    <div class="wrap">
        <h2>Daiquiri Administration</h2>        
        <form method="post" action="options.php">
            <table class="form-table">
                <?php settings_fields('daiquiri_theme'); ?>
                <?php
                foreach (array_keys($daiquiri_theme_options) as $option) :
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
    </div>
    <?php
}

/*
 * Singleton to get and store the layout form daiquiri.
 * The layout is seperated in header and footer wordpress style.
 */

class Daiquiri_Layout {

    public static function getInstance() {
        static $instances = array();

        $className = get_called_class();

        if (!isset($instances[$className])) {
            $instances[$className] = new $className();
        }

        return $instances[$className];
    }

    final private function __clone() {
        
    }

    private function __construct() {
        require_once('HTTP/Request2.php');
        $req = new HTTP_Request2(get_option('daiquiri_layout_url'));
        $req->setMethod('GET');
        $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
        $response = $req->send();
        $body = explode('<!-- content -->', $response->getBody());
        if (count($body) == 2) {
            $this->_header = $body[0];
            $this->_footer = $body[1];
        } else {
            $this->_header = '<h1>Error with theme</h1><div style="visibility:hidden;">';
            $this->_footer = '</div>';
        }
    }

    public function get_header() {
        echo $this->_header;
    }

    public function get_footer() {
        echo $this->_footer;
    }

}

/*
 * Initialize dynamic sidebar
 */

register_sidebar(array(
    'name' => 'SidebarFoo',
    'id' => 'sidebar',
    'before_widget' => '<li>',
    'after_widget' => "</li>",
    'before_title' => "<h3>",
    'after_title' => "</h3>"
));

/*
 * Function to render the comment list
 */

function daiquiri_comment($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    extract($args, EXTR_SKIP);
    ?>
    <div <?php comment_class(empty($args['has_children']) ? '' : 'parent') ?> id="comment-<?php comment_ID() ?>">
        <div class="comment-header">
            <p>
                <a href="<?php comment_link() ?>"></a>
                <?php if ($comment->comment_approved == '0') : ?>
                    <em class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.') ?></em>
                <?php endif; ?>
            </p>
        </div>
        <div class="comment-body">
            <?php comment_text() ?>
        </div>
        <div class="comment-footer">
            by <?php comment_author_link(get_comment_ID()) ?>,
            <?php comment_date() ?>, <?php comment_time('H:i') ?>
            <a href="<?php comment_link() ?>">Permalink</a>
            <?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
        </div>
    </li>
    <?php
}

