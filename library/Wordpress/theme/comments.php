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
?>

<div id="comments">
    <?php if (get_comments_number() > 0): ?>
        <div id="list">
            <h3>Comments</h3>
            <?php wp_list_comments('style=div&callback=daiquiri_comment'); ?>
        </div>
    <?php endif ?>
    <?php
    comment_form(array(
        'title_reply' => 'Write a Reply or Comment',
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
