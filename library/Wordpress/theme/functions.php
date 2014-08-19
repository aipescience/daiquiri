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
        $layoutUrl = DAIQUIRI_URL . '/core/layout/';
        if (strpos($layoutUrl, $siteUrl) !== false) {
            echo '<h1>Error with theme</h1><p>Layout URL is below CMS URL.</p>';
            die(0);
        }

        // construct request
        require_once('HTTP/Request2.php');
        $req = new HTTP_Request2($layoutUrl);
        $req->setConfig(array(
            'ssl_verify_peer' => false, // we trust the certificate here
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
        // get the closing tag of the html head
        $pos = strpos($this->_header,'</head>');

        // echo the header and 'inject' the wp_head hook
        echo substr($this->_header, 0, $pos);
        wp_head();
        echo substr($this->_header, $pos);
    }

    public function get_footer() {
        // get the closing tag of the html body
        $pos = strpos($this->_footer,'</body>');

        // echo the footer and 'inject' the wp_footer hook
        echo substr($this->_footer, 0, $pos);
        wp_footer();
        echo substr($this->_footer, $pos);
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

