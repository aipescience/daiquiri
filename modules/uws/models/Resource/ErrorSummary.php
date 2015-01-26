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

/**
 * XSD class ShortJobDescription
 */
class Uws_Model_Resource_ErrorSummary extends Uws_Model_Resource_Abstract {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->type = "transient";
        $this->hasDetail = false;
        $this->messages = array();
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $error = $xmlDoc->createElementNS($this->nsUws, "uws:errorSummary", "");

        $type = $xmlDoc->createAttribute("type");
        $type->value = $this->type;
        $error->appendChild($type);

        if ($this->hasDetail === true) {
            $hasDetail = $xmlDoc->createAttribute("hasDetail");
            $hasDetail->value = "true";
            $error->appendChild($hasDetail);
        } else {
            $hasDetail = $xmlDoc->createAttribute("hasDetail");
            $hasDetail->value = "false";
            $error->appendChild($hasDetail);
        }

        foreach ($this->messages as $message) {
            $messageElement = $xmlDoc->createElementNS($this->nsUws, "uws:message", $message);
            $error->appendChild($messageElement);
        }

        if ($node === false) {
            $xmlDoc->appendChild($error);
        } else {
            $node->appendChild($error);
        }
    }

}

