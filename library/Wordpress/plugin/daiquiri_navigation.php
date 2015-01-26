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

add_action('wp_update_nav_menu', 'daiquiri_update_nav_menu');

function daiquiri_update_nav_menu() {
    foreach (get_terms( 'nav_menu') as $menu) {
        $html = wp_nav_menu(array(
            'menu' => $menu->name,
            'echo' => false,
            'container' => false,
            'items_wrap' => '%3$s'
        ));

        $filename = DAIQUIRI_NAVIGATION_PATH . '/' . $menu->name . '.html';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $html);
        fclose($fh);
    }
}