<?php
/*
Template Name: News (no title)
*/
?>
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
    	    echo '<li class="news-entry">';
    	    echo '<span class="news-entry-date">';
    	    echo date('F jS, Y', strtotime($post["post_date"]));
    	    echo '</span>';
    	    echo '<a href="' . get_permalink($post["ID"]) . '">' . $post["post_title"].'</a>';
    	    echo '</li>';
            }
            ?>
    	</ul>
    </div>
</div> 

<?php get_footer(); ?>