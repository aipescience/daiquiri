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

<?php get_header(); ?>

<div id="wp-content" class="row">
    <div class="span9 main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="post">
            <h2>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>

            <div class="post-date">by <?php the_author(); ?> on <?php the_date(); ?></div>

            <?php the_content(); ?>

            <div class="post-nav">
                <?php echo get_previous_posts_link(); ?>
                <?php previous_post_link('Previous post: %link&nbsp&nbsp&nbsp'); ?>
                <?php next_post_link('Next post: %link'); ?>
            </div>

            <?php if (comments_open(get_the_ID())): ?>
                <?php comments_template() ?>
            <?php endif ?>
        <?php endwhile; else: ?>
            <p>Sorry, no post was found.</p>
        <?php endif; ?>
        </div>
    </div>
    <div class="span3 sidebar">
        <?php get_sidebar() ?>
    </div>
</div>

<?php get_footer(); ?>