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

class Uws_Model_Init extends Daiquiri_Model_Init {

    /**
     * Returns the acl resources for the uws module. Does nothing so far.
     * @return array $resources
     */
    public function getResources() {
        return array();
    }

    /**
     * Returns the acl rules for the uws module. Does nothing so far.
     * @return array $rules
     */
    public function getRules() {
        return array();
    }

    /**
     * Processes the 'uws' part of $options['config']. Does nothing so far.
     */
    public function processConfig() {

    }

    /**
     * Processes the 'uws' of $options['init']. Does nothing so far.
     */
    public function processInit() {

    }

    /**
     * Initializes the database with the init data for the uws module. Does nothing so far.
     */
    public function init() {

    }

}
