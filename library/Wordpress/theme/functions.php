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
        // sanity check
        $siteUrl = get_option('siteurl');
        $layoutUrl = get_option('daiquiri_url') . '/layout/';
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

        try {
            $response = $req->send();

            if (200 != $response->getStatus()) {
                echo '<h1>Error with theme</h1><p>HTTP request status != 200.</p>';
                die(0);
            }
        } catch (HTTP_Request2_Exception $e) {
            echo '<h1>Error with theme</h1><p>Error with HTTP request.</p>';
            die(0);
        }

        $body = explode('<!-- content -->', $response->getBody());
        if (count($body) == 2) {
            $this->_header = $body[0];
            $this->_footer = $body[1];
        } else {
            echo '<h1>Error with theme</h1><p>Malformatted layout.</p>';
            die(0);
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

