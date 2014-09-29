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

// [dbinfo db="DBName"]
add_shortcode('dbinfo', 'dbinfo_func' );

function dbinfo_func($atts, $content = null) {
    global $wpdb;

    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
    ), $atts ) );

    $query = $wpdb->prepare("SELECT `name`,`description` from `" . DAIQUIRI_DB . "`.`Data_Databases` WHERE `name` = %s",$atts['db']);

    $rows = $wpdb->get_results($query);
    if (count($rows) != 1) {
        return '<p class="text-error">Error with daiquiri dbinfo shortcode.</p>';
    }

    $db = $rows[0];
    $html = "<h3>{$db->name}</h3>";
    $html .= "<p>{$db->description}</p>";

    return $html;
}

// [tableinfo db="DBName" table="tableName"]
add_shortcode('tableinfo', 'tableinfo_func' );

function tableinfo_func($atts, $content = null) {
    global $wpdb;

    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
        'table' => 'NON_EXISTING_TABLE',
    ), $atts ) );

    $query = $wpdb->prepare("SELECT `id`,`name`,`description` from `" . DAIQUIRI_DB . "`.`Data_Tables` WHERE `name` = %s",$atts['table']);
    $rows = $wpdb->get_results($query);
    if (count($rows) != 1) {
        return '<p class="text-error">Error with daiquiri tableinfo shortcode.</p>';
    }

    $table = $rows[0];
    $html = "<h4>{$atts['db']}.{$table->name}</h4>";
    $html .= "<p>{$table->description}</p>";

    $query = $wpdb->prepare("SELECT `name`,`description`,`ucd`,`unit`,`type` from `" . DAIQUIRI_DB . "`.`Data_Columns` WHERE `table_id` = %s ORDER BY `order` ASC",$table->id);

    $columns = $wpdb->get_results($query);

    if (count($columns) < 1) {
        return '<p class="text-error">Error with daiquiri tableinfo shortcode.</p>';
    }

    $html .= '<table class="table table-bordered">';
    $html .= '<thead><tr><th>Column</th><th>Type</th><th>UCD</th><th>Unit</th><th>Description</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($columns as $column) {
        $html .= '<tr>';
        $html .= "<td><strong>{$column->name}</strong></td>";
        $html .= "<td>{$column->type}</td>";
        $html .= "<td>{$column->ucd}</td>";
        $html .= "<td>{$column->unit}</td>";
        $html .= "<td>{$column->description}</td>";
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

// [menu]
add_shortcode('menu', 'menu_func' );

function menu_func($atts) {
    extract(shortcode_atts(array(
        'menu' => null,
    ), $atts ) );

    $string = wp_nav_menu(array(
        'menu' => $menu,
        'echo' => false,
        'container' => 'p'
        ));

    return '<!-- begin -->'.$string.'<!-- end -->';
}