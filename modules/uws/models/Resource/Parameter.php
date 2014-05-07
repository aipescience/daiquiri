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

/**
 * XSD class ShortJobDescription
 */
class Uws_Model_Resource_Parameter extends Uws_Model_Resource_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        parent::__construct();

        $this->id = false;
        $this->byReference = false;
        $this->isPost = false;

        $this->value = false;
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $parameter = $xmlDoc->createElementNS($this->nsUws, "uws:parameter", htmlspecialchars($this->value, ENT_QUOTES));

        if ($this->id !== false) {
            $id = $xmlDoc->createAttribute("id");
            $id->value = $this->id;
            $parameter->appendChild($id);
        }

        if ($this->byReference === true) {
            $byReference = $xmlDoc->createAttribute("byReference");
            $byReference->value = "true";
            $parameter->appendChild($byReference);
        }

        if ($this->isPost === true) {
            $isPost = $xmlDoc->createAttribute("isPost");
            $isPost->value = "true";
            $parameter->appendChild($isPost);
        }

        if ($node === false) {
            $xmlDoc->appendChild($parameter);
        } else {
            $node->appendChild($parameter);
        }
    }

}

