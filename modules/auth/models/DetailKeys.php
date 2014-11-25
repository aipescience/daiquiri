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

class Auth_Model_DetailKeys extends Daiquiri_Model_Table {

    public static $types = array('default','checkbox','radio','select','multiselect');

    /**
     * Constructor. Sets resource and tablename.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Auth_DetailKeys');
    }

    /**
     * Returns all participants detail keys
     * @return array $response
     */
    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    /**
     * Returns one specific participant detail key.
     * @param int $id id of the participant detail key
     * @return array $response
     */
    public function show($id) {
        return $this->getModelHelper('CRUD')->show($id);
    }

    /**
     * Creates a new participant detail key.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        return $this->getModelHelper('CRUD')->create($formParams);
    }

    /**
     * Updates an participant detail key.
     * @param int $id id of the participant detail key
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->update($id, $formParams);
    }

    /**
     * Deletes a participant detail key.
     * @param int $id id of the participant detail key
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams);
    }
}