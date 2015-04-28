<?php
/*
Template Name: News (no title)
*/
?>
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
?>

<?php get_header(); ?>

<div id="wp-content" class="main">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="post">
            <?php the_content(); ?>

            <?php edit_post_link('Edit Page', '<div>', '</div>'); ?>

            <?php if (comments_open(get_the_ID())): ?>
                <?php comments_template() ?>
            <?php endif ?>
        </div>
    <?php endwhile; else: ?>
        <p>Sorry, no page found.</p>
    <?php endif; ?>

    <div id="recent-news">
        <h3>Recent News</h3>
        <ul class="unstyled">
            <?php
            foreach (wp_get_recent_posts() as $post) {
                $content = explode('<!--more-->',$post["post_content"])[0];
                echo '<li class="news-entry">';
                echo '<div class="pull-left onehundredeighty" class="news-entry-date">';
                echo date('F jS, Y', strtotime($post["post_date"]));
                echo '</div>';
                echo '<div class="align-form-horizontal">';
                echo '<p><a href="' . get_permalink($post["ID"]) . '">' . $post["post_title"] .'</a></p>';
                echo '<p>' . $content . '</p>';
                echo '</div>';
                echo '</li>';
            }
            ?>
        </ul>
    </div>
</div>

<?php get_footer(); ?>