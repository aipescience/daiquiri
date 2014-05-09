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

class Daiquiri_Model_Resource_Adapter extends Daiquiri_Model_Resource_Abstract {

    /**
     * Database adapter to be used with this resource. If null the default adapter will be used.
     * @var Zend_Db_Adapter
     */
    private $_adapter = null;

    /**
     * Sets the database adapter for this resource.
     * @param Zend_Db_Adapter database adapter
     */
    public function setAdapter($adapter) {
        $this->_adapter = $adapter;
    }

    /**
     * Returns the database adapter for this resource. Returns the default adapter if no adapter is set.
     * @return Zend_Db_Adapter
     */

    public function getAdapter() {
        if ($this->_adapter === null) {
            return Zend_Db_Table::getDefaultAdapter();
        } else {
            return $this->_adapter;
        }
    }

    /**
     * Returns the name of the database database configured in the adapter for this resource.
     * @return string $dbname
     */

    public function getDbname() {
        $config = $this->getAdapter()->getConfig();
        return $config['dbname'];
    }

    /**
     * Returns a Daiquiri select object from a given array with sql options.
     * @param Array $sqloptions array of sqloptions (start,limit,order,where,from)
     * @return Daiquiri_Db_Select
     */
    public function select($sqloptions = array()) {
        return new Daiquiri_Db_Select($this->getAdapter(), $sqloptions);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchAll(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object as associative array.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchAssoc(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchAssoc($select);
    }

    /**
     * Fetches all rows from the databases adapter specified by the select object as key value pairs.
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @return array $rows
     */
    public function fetchPairs(Daiquiri_Db_Select $select = null) {
        return $this->getAdapter()->fetchPairs($select);
    }

    /**
     * Fetches one (and only one) row from the database specfied by the select object. 
     * Raises an Exception when more than one row is found. Returns an empty arrau when 
     * no rows are found
     * @param Daiquiri_Db_Select $select daiquiri select object
     * @throws Exception
     * @return array $row
     */
    public function fetchOne(Daiquiri_Db_Select $select = null) {
        $rows = $this->getAdapter()->fetchAll($select);
        if (empty($rows)) {
            return false;
        } else if (count($rows) > 1) {
            throw new Exception('More than one row returned in ' . get_class($this) . '::' . __FUNCTION__ . '()');
        } else {
            return $rows[0];
        }
    }

    /**
     * Quotes a variable number of strings as databases idetifiers.
     * @param string $argument string to be quoted
     * @param string $argument string to be quoted
     * @param string $argument string to be quoted
     * @return string $identifier
     */
    public function quoteIdentifier() {
        // get the arguments
        $arguments = func_get_args();

        $identifier = array();
        foreach($arguments as $argument) {
            $identifier[] = $this->getAdapter()->quoteIdentifier($argument);
        }

        return implode('.', $identifier);
    }

}
