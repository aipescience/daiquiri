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
class Uws_Model_Resource_ShortJobDescriptionType extends Uws_Model_Resource_Abstract {

    /**
     * Constructor.
     */
    public function __construct($name) {
        parent::__construct();

        $this->name = $name;
        $this->id = false;
        $this->jobId = false;
        $this->runId = false;
        $this->ownerId = false;
        $this->creationTime = false;
        $this->reference = new Uws_Model_Resource_ReferenceAttrGrp();
        $this->phase = array();
    }

    public function toXML(&$xmlDoc, &$node = false) {
        $job = $xmlDoc->createElementNS($this->nsUws, "uws:{$this->name}");

        // note: this id must be the jobId for the job (not job name or resultTable name),
        // This must be the same id as used in the href-element
        $id = $xmlDoc->createAttribute("id");
        $id->value = htmlspecialchars($this->id, ENT_QUOTES);
        $job->appendChild($id);

        // add optional attributes allowed by UWS 1.1 update:
        // use our job-name or table-name for runId
        $runId = $xmlDoc->createAttribute("runId");
        $runId->value = htmlspecialchars($this->runId, ENT_QUOTES);
        $job->appendChild($runId);

        $ownerId = $xmlDoc->createAttribute("ownerId");
        $ownerId->value = htmlspecialchars($this->ownerId, ENT_QUOTES);
        $job->appendChild($ownerId);

        $creationTime = $xmlDoc->createAttribute("creationTime");
        $creationTime->value = htmlspecialchars($this->creationTime, ENT_QUOTES);
        $job->appendChild($creationTime);

        $this->reference->toXML($xmlDoc, $job);

        foreach ($this->phase as $phase) {
            $phaseElement = $xmlDoc->createElementNS($this->nsUws, "uws:phase", $phase);
            $job->appendChild($phaseElement);
        }

        if ($node === false) {
            $xmlDoc->appendChild($job);
        } else {
            $node->appendChild($job);
        }
    }

}

