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

class Daiquiri_Model_Helper_Webhook extends Daiquiri_Model_Helper_Abstract {

    public function send($url, $data) {
        if (empty($url)) {
            throw new Exception('$url not provided in Daiquiri_Model_Helper_Webhook::send()');
        }
        if (empty($data)) {
            throw new Exception('$data not provided in Daiquiri_Model_Helper_Webhook::send()');
        }

        $json = Zend_Json::encode($data);
        $client = new Zend_Http_Client($url);
        $client->setAdapter(new Zend_Http_Client_Adapter_Curl());
        $client->setMethod(Zend_Http_Client::POST);
        $client->setRawData($json, 'application/json');

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {
            // fail silently
        }
    }
}