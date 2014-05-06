<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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

/**
 * XSD class reference
 */
abstract class Uws_Model_Resource_Abstract extends Daiquiri_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        $this->nsUws = "http://www.ivoa.net/xml/UWS/v1.0";
        $this->nsXLink = "http://www.w3.org/1999/xlink";
        $this->nsXsi = "http://www.w3.org/2001/XMLSchema-instance";
        $this->validateSchema = false;
    }

    abstract public function toXML(&$xmlDoc, &$node = false);
}
