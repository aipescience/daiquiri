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

// [userinfo]
add_shortcode('userinfo', 'userinfo_func' );

function userinfo_func($atts) {
    global $wpdb;

    extract(shortcode_atts(array(
        'id' => null,
        'username' => null,
        'email' => null,
    ), $atts ) );

    if (($id === null && $username === null) || ($id !== null && $username !== null)) {
        return '<p class="text-error">Error with daiquiri userinfo shortcode. Please give username OR id.</p>';
    }

    $u = '`' . DAIQUIRI_DB . '`.`Auth_User`';
    $d = '`' . DAIQUIRI_DB . '`.`Auth_Details`';

    $querystring = "
        SELECT email,d1.value as firstname,d2.value as lastname
        FROM {$u} as u
        JOIN {$d} as d1 ON d1.user_id = u.id AND d1.key = 'firstname'
        JOIN {$d} as d2 ON d2.user_id = u.id AND d2.key = 'lastname'
    ";

    if ($id !== null) {
        $query = $wpdb->prepare($querystring . 'WHERE u.id = %s', $id);
    } else {
        $query = $wpdb->prepare($querystring . 'WHERE u.username = %s', $username);
    }

    $rows = $wpdb->get_results($query);
    if (count($rows) != 1) {
        return '<p class="text-error">Error with daiquiri userinfo shortcode. User not found.</p>';
    }

    $user = $rows[0];

    if (empty($email)) {
        return "<span>{$user->firstname} {$user->lastname}</span>";
    } else {
        return "<a href=\"mailto:{$user->email}\">{$user->firstname} {$user->lastname}</a>";
    }
}

// [licenseinfo]
add_shortcode('license', 'license_func' );

function license_func($atts) {
    extract(shortcode_atts(array(
        'license' => null,
    ), $atts ) );

    // more licenses to be implemented
    $html = '<p>This data set is released under the <a href="http://creativecommons.org/publicdomain/zero/1.0/">Creative Commons CC0</a> waiver. We do not endorse any works, scientific or otherwise, produced using these data.</p>';

    return $html;
}

// [disclaimer]
add_shortcode('identifier', 'identifier_func' );

function identifier_func($atts) {
    extract(shortcode_atts(array(
        'doi' => null,
    ), $atts ) );

    // more identifier systems to be implemented
    if ($doi) $html = "<p>Please cite this data set using the unique digital object identifier <a href=\"https://doi.org/{$doi}\">doi:{$doi}</a>.";

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
