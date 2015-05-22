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

// [disclaimer]
add_shortcode('disclaimer', 'disclaimer_func' );

function disclaimer_func($atts) {
    extract(shortcode_atts(array(
        'doi' => null,
        'license' => null,
    ), $atts ) );

    $html = '';

    if ($license === 'cc0') $html .= '<p>This data set is released under the <a href="http://creativecommons.org/publicdomain/zero/1.0/">Creative Commons CC0</a> waiver. We do not endorse any works, scientific or otherwise, produced using these data.</p>';

    if ($doi) $html .= "<p>Please cite this data set using the unique permanent identifier <a href=\"http://dx.doi.org/{$doi}\">doi:{$doi}</a>.";

    return $html;
}

// [dbinfo db="DBName"]
add_shortcode('dbinfo', 'dbinfo_func' );

function dbinfo_func($atts, $content = null) {
    global $wpdb;

    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
    ), $atts ) );

    $databases = '`' . DAIQUIRI_DB . '`.`Data_Databases`';

    $query = $wpdb->prepare("
        SELECT description
        FROM {$databases}
        WHERE `name` = %s
    ",$atts['db']);

    $rows = $wpdb->get_results($query);
    if (count($rows) != 1) {
        return '<p class="text-error">Error with daiquiri dbinfo shortcode.</p>';
    }

    $db = $rows[0];
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

    $databases = '`' . DAIQUIRI_DB . '`.`Data_Databases`';
    $tables    = '`' . DAIQUIRI_DB . '`.`Data_Tables`';

    $query = $wpdb->prepare("
        SELECT t.description
        FROM {$tables} as t
        JOIN {$databases} as d ON d.id = t.database_id
        WHERE d.name = %s AND t.name = %s
    ",$atts['db'],$atts['table']);

    $rows = $wpdb->get_results($query);
    if (count($rows) != 1) {
        return '<p class="text-error">Error with daiquiri tableinfo shortcode.</p>';
    }

    $table = $rows[0];
    return "<p>{$table->description}</p>";
}

// [columninfo db="DBName" table="tableName"]
add_shortcode('columninfo', 'columninfo_func' );

function columninfo_func($atts, $content = null) {
    global $wpdb;

    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
        'table' => 'NON_EXISTING_TABLE',
    ), $atts ) );

    $databases = '`' . DAIQUIRI_DB . '`.`Data_Databases`';
    $tables    = '`' . DAIQUIRI_DB . '`.`Data_Tables`';
    $columns   = '`' . DAIQUIRI_DB . '`.`Data_Columns`';

    $query = $wpdb->prepare("
        SELECT c.name,c.description,c.ucd,c.unit,c.type
        FROM {$columns} as c
        JOIN {$tables} as t ON t.id = c.table_id
        JOIN {$databases} as d ON d.id = t.database_id
        WHERE d.name = %s AND t.name = %s
        ORDER BY c.`order` ASC
    ",$atts['db'],$atts['table']);

    $columns = $wpdb->get_results($query);

    if (count($columns) < 1) {
        return '<p class="text-error">Error with daiquiri columninfo shortcode.</p>';
    }

    $html .= '<table class="table table-bordered">';
    $html .= '<thead><tr><th>Column</th><th>Type</th><th>UCD</th><th>Unit</th><th>Description</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($columns as $column) {
        $ucd = str_replace(';','<br />',$column->ucd);
        $html .= '<tr>';
        $html .= "<td><strong>{$column->name}</strong></td>";
        $html .= "<td>{$column->type}</td>";
        $html .= "<td>{$ucd}</td>";
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
