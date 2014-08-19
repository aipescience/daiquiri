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

// [tableinfo db="DBName" table="tableName"]
add_shortcode('tableinfo', 'tableinfo_func' );

function tableinfo_func($atts, $content = null) {
    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
        'table' => 'NON_EXISTING_TABLE',
    ), $atts ) );

    $layoutUrl = DAIQUIRI_URL . '/data/tables/show/db/' . $atts['db'] . '/table/' . $atts['table'];

    // construct request
    require_once('HTTP/Request2.php');
    $req = new HTTP_Request2($layoutUrl);
    $req->setConfig(array(
        'connect_timeout' => 2,
        'timeout' => 3
    ));
    $req->setMethod('GET');
    $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
    $req->setHeader('Accept: application/html');

    try {
        $response = $req->send();
        $status = $response->getStatus();
        $body = $response->getBody();
    } catch (HTTP_Request2_Exception $e) {
        return '<h5>Error with daiquiri tableinfo</h5><p>Error with HTTP request.</p>';
    }

    if ($status != 200) {
        return "<h5>Error with daiquiri tableinfo</h5><p>Error with HTTP request. Error code {$status}</p>";
    }

    return $body;
}

// [tableinfo db="DBName"]
add_shortcode('dbinfo', 'dbinfo_func' );

function dbinfo_func($atts, $content = null) {
    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
    ), $atts ) );

    $layoutUrl = DAIQUIRI_URL . '/data/databases/show/db/' . $atts['db'];

    // construct request
    require_once('HTTP/Request2.php');
    $req = new HTTP_Request2($layoutUrl);
    $req->setConfig(array(
        'connect_timeout' => 2,
        'timeout' => 3
    ));
    $req->setMethod('GET');
    $req->addCookie("PHPSESSID", $_COOKIE["PHPSESSID"]);
    $req->setHeader('Accept: application/html');

    try {
        $response = $req->send();
        $status = $response->getStatus();
        $body = $response->getBody();
    } catch (HTTP_Request2_Exception $e) {
        return '<h5>Error with daiquiri dbinfo</h5><p>Error with HTTP request.</p>';
    }

    if ($status != 200) {
        return "<h5>Error with daiquiri dbinfo</h5><p>Error with HTTP request. Error code {$status}</p>";
    }

    return $body;
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