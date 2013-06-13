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
        'fields' => array(
            'author' => '<p class="comment-form-author"><input class="span9" placeholder="' . __('Name', 'domainreference') . ( $req ? ' (required)' : '' ) . '" id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' /></p>',
            'email' => '<p class="comment-form-email"><input class="span9" placeholder="' . __('Email', 'domainreference') . ( $req ? ' (required)' : '' ) . '" id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' /></p>',
            'url' => '<p class="comment-form-url"><input class="span9" placeholder="' . __('Website', 'domainreference') . /* ( $req ? ' (required)' : '' ) . */ '" id="url" name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>'
        ),
        'comment_field' => '<p class="comment-form-comment"><textarea class="span9" placeholder="' . _x('Comment', 'noun') . '" id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>'
    ));
    ?>

    <script type="text/javascript">
        $('#submit').addClass('btn');
    </script>
</div>
