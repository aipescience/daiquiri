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
?>

<div id="comments">
    <?php if (get_comments_number() > 0): ?>
        <div id="list">
            <h3>Comments</h3>
            <?php wp_list_comments('style=div&callback=daiquiri_comment'); ?>
        </div>
    <?php endif ?>

    <p>
        <a href="" id="comment-open-write-link">Write a comment</a>
        <a href="" id="comment-close-write-link">Close comment form</a>

        <script>
            $(document).ready(function() {
                if (window.location.hash.indexOf('#comment') != -1 ||
                    window.location.hash.indexOf('#respond') != -1) {
                    $('#comment-open-write-link').trigger('click');
                }
            });
            $('#comment-open-write-link').on('click', function() {
                $('#respond').show();
                $('#comment-open-write-link').hide();
                $('#comment-close-write-link').show();
                return false;
            });
            $('#comment-close-write-link').on('click', function() {
                $('#respond').hide();
                $('#comment-open-write-link').show();
                $('#comment-close-write-link').hide();
                return false;
            });
        </script>
    </p>

    <?php
    comment_form(array(
        'title_reply' => '',
        'title_reply_to' =>  '',
        'comment_notes_before' => '',
        'logged_in_as' => '
<fieldset class="form-horizontal">
    <div class="control-group">
        <label class="control-label">Logged in as</label>
        <div class="controls">
            <div class="input-block-level">' . sprintf( __( '<a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</div>
        </div>
    </div>
</fieldset>',
        'comment_notes_after' => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:<br />%s' ), ' <span class="mono">' . trim(allowed_tags()) . '</span>' ) . '</p><p>Your email address will not be published.</p>',
        'fields' => array(
            'author' => '
<fieldset class="form-horizontal">
    <div class="control-group comment-form-author">
        <label class="control-label" for="inputAuthor">' . __('Name', 'domainreference') . '</label>
        <div class="controls">
            <input id="inputAuthor" class="input-block-level" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' />
        </div>
    </div>
</fieldset>',
            'email' => '
<fieldset class="form-horizontal">
    <div class="control-group comment-form-email">
        <label class="control-label" for="inputEmail">' . __('Email', 'domainreference') . '</label>
        <div class="controls">
            <input id="inputEmail" class="input-block-level" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' />
        </div>
    </div>
</fieldset>',
            'url' => '
<fieldset class="form-horizontal">
    <div class="control-group comment-form-url">
        <label class="control-label" for="inputUrl">' . __('Website', 'domainreference') . ' (not required)</label>
        <div class="controls">
            <input id="inputUrl" class="input-block-level" name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30"' . $aria_req . ' />
        </div>
    </div>
</fieldset>'
        ),
        'comment_field' => '
<fieldset class="form-horizontal">
    <div class="control-group comment-form-comment">
        <label class="control-label" for="inputComment">' . _x('Comment', 'noun') . '</label>
        <div class="controls">
            <textarea id="inputComment" class="input-block-level" name="comment" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30"' . $aria_req . ' rows="8"></textarea>
        </div>
    </div>
</fieldset>'
    ));
    ?>

    <script type="text/javascript">
        $('#submit').addClass('btn');
    </script>
</div>
