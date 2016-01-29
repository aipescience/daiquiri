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
    'name' => 'Sidebar',
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

/*
 * Modyfy style of admin interface
 */

add_action('admin_head', 'daiquiri_admin_style');

function daiquiri_admin_style() {
    echo '<style>
        #wp-auth-check-wrap {
            display: none !important;
        }
    </style>'.PHP_EOL;
}

/*
 * Admin Video/Image Box
 * see http://www.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/
 */

add_action( 'load-post.php', 'daiquiri_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'daiquiri_post_meta_boxes_setup' );

function daiquiri_post_meta_boxes_setup() {
    add_action( 'add_meta_boxes', 'daiquiri_add_post_meta_boxes' );
    add_action( 'save_post', 'daiquiri_save_video_meta_box', 10, 2 );
    add_action( 'save_post', 'daiquiri_save_image_meta_box', 10, 2 );
}

function daiquiri_add_post_meta_boxes() {
    add_meta_box(
        'daiquiri-video-meta-box',
        esc_html__( 'Video', 'example' ),  // Title
        'daiquiri_video_meta_box',         // Callback function
        'post',                            // Admin page (or post type)
        'normal',                          // Context
        'default'                          // Priority
    );
    add_meta_box(
        'daiquiri-image-meta-box',
        esc_html__( 'Image', 'example' ),  // Title
        'daiquiri_image_meta_box',         // Callback function
        'post',                            // Admin page (or post type)
        'normal',                          // Context
        'default'                          // Priority
    );
}

function daiquiri_video_meta_box($object, $box) {
    wp_nonce_field( basename( __FILE__ ), 'daiquiri_video_meta_box_nonce' );

    $post_id = $object->ID;
    $poster = get_post_meta($post_id,'video_poster',true);
    $mp4    = get_post_meta($post_id,'video_mp4',true);
    $avi    = get_post_meta($post_id,'video_avi',true);
    $ogg    = get_post_meta($post_id,'video_ogg',true);
    $webm   = get_post_meta($post_id,'video_webm',true);

    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-poster">' . _e("Video poster",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-video-meta-box-poster" id="daiquiri-video-meta-box-poster" value="' . $poster . '"/>';
    echo '</p>';
    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-mp4">' . _e("Video mp4 file",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-video-meta-box-mp4" id="daiquiri-video-meta-box-mp4" value="' . $mp4 . '"/>';
    echo '</p>';
    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-avi">' . _e("Video avi file",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-video-meta-box-avi" id="daiquiri-video-meta-box-avi" value="' . $avi . '"/>';
    echo '</p>';
    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-ogg">' . _e("Video ogg file",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-video-meta-box-ogg" id="daiquiri-video-meta-box-ogg" value="' . $ogg . '"/>';
    echo '</p>';
    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-webm">' . _e("Video webm file",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-video-meta-box-webm" id="daiquiri-video-meta-box-webm" value="' . $webm . '"/>';
    echo '</p>';
}

function daiquiri_save_video_meta_box($post_id, $post) {
    /* Verify the nonce before proceeding. */
    if (!isset($_POST['daiquiri_video_meta_box_nonce']) || !wp_verify_nonce( $_POST['daiquiri_video_meta_box_nonce'],basename(__FILE__))) {
        return $post_id;
    }

    /* Get the post type object. */
    $post_type = get_post_type_object($post->post_type);

    /* Check if the current user has permission to edit the post. */
    if (!current_user_can($post_type->cap->edit_post,$post_id)) {
        return $post_id;
    }

    /* Get the posted data and sanitize it */
    $new = array();
    $new['video_poster'] = (isset($_POST['daiquiri-video-meta-box-poster']) ? sanitize_text_field($_POST['daiquiri-video-meta-box-poster']) : '');
    $new['video_mp4']    = (isset($_POST['daiquiri-video-meta-box-mp4']) ? sanitize_text_field($_POST['daiquiri-video-meta-box-mp4']) : '');
    $new['video_avi']    = (isset($_POST['daiquiri-video-meta-box-avi']) ? sanitize_text_field($_POST['daiquiri-video-meta-box-avi']) : '');
    $new['video_ogg']    = (isset($_POST['daiquiri-video-meta-box-ogg']) ? sanitize_text_field($_POST['daiquiri-video-meta-box-ogg']) : '');
    $new['video_webm']   = (isset($_POST['daiquiri-video-meta-box-webm']) ? sanitize_text_field($_POST['daiquiri-video-meta-box-webm']) : '');

    /* Get the current data */
    $current = array();
    $current['video_poster'] = get_post_meta($post_id,'video_poster',true);
    $current['video_mp4']    = get_post_meta($post_id,'video_mp4',true);
    $current['video_avi']    = get_post_meta($post_id,'video_avi',true);
    $current['video_ogg']    = get_post_meta($post_id,'video_ogg',true);
    $current['video_webm']    = get_post_meta($post_id,'video_webm',true);

    foreach (array('video_poster','video_mp4','video_avi','video_ogg','video_webm') as $key) {
        if ($new[$key] && '' == $current[$key]) {
            /* If a new meta value was added and there was no previous value, add it. */
            add_post_meta($post_id, $key, $new[$key], true);
        } elseif ($new[$key] && $new[$key] != $current[$key]) {
            /* If the new meta value does not match the old value, update it. */
            update_post_meta($post_id, $key, $new[$key]);
        } elseif ('' == $new[$key] && $current[$key]) {
            /* If there is no new meta value but an old value exists, delete it. */
            delete_post_meta($post_id, $key, $new[$key]);
        }
    }
}

function daiquiri_image_meta_box($object, $box) {
    wp_nonce_field( basename( __FILE__ ), 'daiquiri_image_meta_box_nonce' );

    $post_id = $object->ID;
    $small = get_post_meta($post_id,'image_small',true);
    $large = get_post_meta($post_id,'image_large',true);

    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-small">' . _e("Image source (small, 320px)",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-image-meta-box-small" id="daiquiri-image-meta-box-small" value="' . $small . '"/>';
    echo '</p>';
    echo '<p>';
    echo '<label for="daiquiri-video-meta-box-large">' . _e("Image source (large)",'example') . '</label>';
    echo '<input class="widefat" type="text" name="daiquiri-image-meta-box-large" id="daiquiri-image-meta-box-large" value="' . $large . '"/>';
    echo '</p>';
}

function daiquiri_save_image_meta_box($post_id, $post) {
    /* Verify the nonce before proceeding. */
    if (!isset($_POST['daiquiri_image_meta_box_nonce']) || !wp_verify_nonce( $_POST['daiquiri_image_meta_box_nonce'],basename(__FILE__))) {
        return $post_id;
    }

    /* Get the post type object. */
    $post_type = get_post_type_object($post->post_type);

    /* Check if the current user has permission to edit the post. */
    if (!current_user_can($post_type->cap->edit_post,$post_id)) {
        return $post_id;
    }

    /* Get the posted data and sanitize it */
    $new = array();
    $new['image_small'] = (isset($_POST['daiquiri-image-meta-box-small']) ? sanitize_text_field($_POST['daiquiri-image-meta-box-small']) : '');
    $new['image_large'] = (isset($_POST['daiquiri-image-meta-box-large']) ? sanitize_text_field($_POST['daiquiri-image-meta-box-large']) : '');

    /* Get the current data */
    $current = array();
    $current['image_small'] = get_post_meta($post_id,'image_small',true);
    $current['image_large'] = get_post_meta($post_id,'image_large',true);

    foreach (array('image_small','image_large') as $key) {
        if ($new[$key] && '' == $current[$key]) {
            /* If a new meta value was added and there was no previous value, add it. */
            add_post_meta($post_id, $key, $new[$key], true);
        } elseif ($new[$key] && $new[$key] != $current[$key]) {
            /* If the new meta value does not match the old value, update it. */
            update_post_meta($post_id, $key, $new[$key]);
        } elseif ('' == $new[$key] && $current[$key]) {
            /* If there is no new meta value but an old value exists, delete it. */
            delete_post_meta($post_id, $key, $new[$key]);
        }
    }
}

function daiquiri_the_video($width) {
    $post_id = get_the_ID();
    $poster = get_post_meta($post_id,'video_poster',true);
    $mp4    = get_post_meta($post_id,'video_mp4',true);
    $avi    = get_post_meta($post_id,'video_avi',true);
    $ogg    = get_post_meta($post_id,'video_ogg',true);
    $webm   = get_post_meta($post_id,'video_webm',true);

    if (!empty($poster) || !empty($mp4) || ! empty($ogg)) {
        echo '<div style="width: ' . $width . 'px;">';
        echo '<video poster="' . $poster . '" controls="controls" style="width: 100%;">';

        if (!empty($mp4)) echo '<source src="' . $mp4 . '" type="video/mp4" />';
        if (!empty($avi)) echo '<source src="' . $avi . '" type="video/avi" />';
        if (!empty($ogg)) echo '<source src="' . $ogg . '" type="video/ogg" />';
        if (!empty($webm)) echo '<source src="' . $webm . '" type="video/webm" />';
        echo 'Your browser does not support the video tag.</video></div>';
    }
}

function daiquiri_the_image($width) {
    $post_id = get_the_ID();
    $small = get_post_meta($post_id,'image_small',true);
    $large = get_post_meta($post_id,'image_large',true);
    $alt = get_the_title($post_id);

    if (!empty($small)) {
        echo '<div style="width: ' . $width . 'px;">';
        if (!empty($large)) echo '<a href="' . $large . '">';
        if ($width > 320 && !empty($large)) {
            echo '<img src="' . $large . '" alt="' . $alt . '" />';
        } else {
            echo '<img src="' . $small . '" alt="' . $alt . '" />';
        }
        if (!empty($large)) echo '</a>';
        echo '</div>';
    }
}
