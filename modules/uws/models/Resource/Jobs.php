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
 * XSD class reference
 */
class Uws_Model_Resource_Jobs extends Uws_Model_Resource_Abstract {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->jobref = array();
    }

    public function addJob($id, $href, array $phases) {
        $newJob = new Uws_Model_Resource_ShortJobDescriptionType("jobref");

        $newJob->id = $id;
        $newJob->reference->href = $href;
        $newJob->phase = $phases;

        $this->jobref[] = $newJob;
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $root = $xmlDoc->createElementNS($this->nsUws, "uws:jobs");
        $xmlDoc->appendChild($root);
        // add version attribute (required by UWS 1.1)
        $version = $xmlDoc->createAttribute('version');
        $version->value = $this->version;
        $root->appendChild($version);

        foreach ($this->jobref as $job) {
            $job->toXML($xmlDoc, $root);
        }

        if ($this->validateSchema == true) {
            if ($xmlDoc->schemaValidate("http://www.ivoa.net/xml/UWS/v1.0") === false) {
                throw new Exception("UWS Jobs: XML is not a valid UWS schema");
            }
        }
    }

}
