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
        <h2>
            Search results for "<?php 
            echo $_GET['s'];
            ?>".
        </h2>
        <p>
            There are <?php 
            global $wp_query;
            echo $wp_query->found_posts;
            ?> page(s) matching the search query.
        </p>        

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <div class="post">
                <h3>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>

                <p>
                    <?php 
                    $content = get_the_content();
                    $trimmed_content = wp_trim_words($content, 40, '<a href="'. get_permalink() .'"> ... more</a>' );
                    echo $trimmed_content;
                    ?>
                </p>
            </div>

        <?php endwhile; else: ?>
            <p>Sorry, no page found.</p>
        <?php endif; ?>
    </div>
    <div class="span3 sidebar">
        <?php get_sidebar(); ?>
    </div>
</div>   

<?php get_footer(); ?>
