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

<?php Daiquiri_Layout::getInstance()->get_header(); ?>

<div class="row">
    <div id="wp-content"  class="span9">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <h2><?php the_title(); ?></h2>

                <?php the_content(); ?>

                <div class="post-footer">
                    This post was first published on 
                    <?php the_date(); ?>
                    and last modified on 
                    <?php the_modified_date(); ?>.
                </div>

                <div class="post-nav">
                    <?php echo get_previous_posts_link(); ?>
                    <?php previous_post_link('Previous post: %link&nbsp&nbsp&nbsp'); ?>
                    <?php next_post_link('Next post: %link'); ?>
                </div>

                <?php if (comments_open(get_the_ID())): ?>
                    <?php comments_template() ?>
                <?php endif ?>
            <?php
            endwhile;
        else:
            ?>
            <p>Sorry, no post was found.</p>
<?php endif; ?>
    </div>
    <div id="wp-sidebar" class="span3">
<?php get_sidebar() ?>
    </div>
</div>

<?php Daiquiri_Layout::getInstance()->get_footer(); ?>