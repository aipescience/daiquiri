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

// [tableinfo db="DBName" table="tableName"]
add_shortcode('tableinfo', 'tableinfo_func' );

function tableinfo_func($atts, $content = null) {
    extract(shortcode_atts(array(
        'db' => 'NON_EXISTING_DATABASE',
        'table' => 'NON_EXISTING_TABLE',
    ), $atts ) );

    $layoutUrl = get_option('daiquiri_url') . '/data/tables/show/db/' . $atts['db'] . '/table/' . $atts['table'];

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

    $layoutUrl = get_option('daiquiri_url') . '/data/databases/show/db/' . $atts['db'];

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