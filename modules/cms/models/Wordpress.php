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

/**
 * Model for interfacing with the wordpress cms
 */
class Cms_Model_Wordpress extends Daiquiri_Model_Abstract {

    public function login($username, $password) {
        // make POST request to wordpress
        $uri = Daiquiri_Config::getInstance()->getSiteUrl() . Daiquiri_Config::getInstance()->cms->url . '/wp-login.php';
        $client = new Zend_Http_Client($uri, array('maxredirects' => 0));
        $client->setMethod('POST');
        $client->setEncType('application/x-www-form-urlencoded');
        $client->setParameterPost(array(
            'log' => $username,
            'pwd' => $password
        ));
        $response = $client->request();

        // return the cookies or throw an error
        if ($response->getStatus() === 302) {
            return $this->_cookies($response);
        } else if ($response->getStatus() === 404) {
            return array();
        } else {
            throw new Exception('Status ' . $response->getStatus() . ' in http request to ' . $uri . '.');
        }
    }

    public function logout() {
        // make first request to get the nonce
        $uri = Daiquiri_Config::getInstance()->getSiteUrl() . Daiquiri_Config::getInstance()->cms->url . 'wp-login.php?action=logout';
        $client = new Zend_Http_Client($uri, array('keepalive' => true, 'maxredirects' => 0));
        $response = $client->request();

        // get the nonce by regex
        $matches = array();
        preg_match('`' . preg_quote("_wpnonce=") . '(.*?)' . preg_quote("'>log out</a>") . '`', $response->getBody(), $matches);
        $nonce = $matches[1];
        $logoutUri = $uri . '&_wpnonce=' . $nonce;

        // make second request to log out for real this time
        $client->resetParameters();
        $client->setUri($logoutUri);
        $response = $client->request();

        // return the cookies or throw an error
        if ($response->getStatus() === 302) {
            return $this->_cookies($response);
        } else if ($response->getStatus() === 404) {
            return array();
        } else {
            throw new Exception('Status ' . $response->getStatus() . ' in http request ' . $uri . '.');
        }
    }

    protected function _cookies($response) {
        $cookies = array();
        $cookieHeaders = $response->getHeader('Set-cookie');
        if (!empty($cookieHeaders)) {
            foreach ($cookieHeaders as $cookie) {
                $cookies[] = $cookie;
            }
        }
        return $cookies;
    }

}
