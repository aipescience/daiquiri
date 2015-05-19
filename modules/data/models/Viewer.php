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

require_once(Daiquiri_Config::getInstance()->core->libs->phpSqlParser . '/lexer/PHPSQLLexer.php');

class Data_Model_Viewer extends Daiquiri_Model_Table {

    /**
     * Construtor. Sets resource and columns.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Viewer');
    }

    /**
     * Returns the columns of a given table and database.
     * @param array $params get params of the request
     * @return array
     */
    public function cols(array $params = array()) {
        // get db and table from params
        if (empty($params['db']) || empty($params['table'])) {
            return array('status' => 'error');
        } else {
            $db = $params['db'];
            $table = $params['table'];
        }

        // init table
        $this->getResource()->init($params['db'], $params['table']);

        // get columns from the database
        $colnames = array_keys($this->getResource()->fetchCols());

        // obtain table metadata
        $tablesResource = new Data_Model_Resource_Tables();
        $tableMeta = $tablesResource->fetchRowByName($db,$table,true);
        if ($tableMeta === false) {
            // this table is not in the metadata table - let's see if we can get
            // further information from the table itself
            $descResource = new Data_Model_Resource_Description();
            $descResource->init($params['db']);
            $tableMeta = $descResource->describeTable($params['table']);
        }

        // construct metadata array
        $meta = array();
        foreach ($tableMeta['columns'] as $key => $colMeta) {
            $meta[$colMeta['name']] = array(
                'id' => $key,
                'ucd' => explode(';',str_replace(' ','',$colMeta['ucd']))
            );
        }

        // return columns of this table
        $cols = array();
        foreach ($colnames as $colname) {
            $col = array(
                'id' => $meta[$colname]['id'],
                'name' => $colname,
                'sortable' => true,
                'ucfirst' => false,
                'ucd' => $meta[$colname]['ucd']
            );

            // add removenewline flag if this is set in the config
            if (Daiquiri_Config::getInstance()->data->viewer->columnWidth) {
                $col['width'] = Daiquiri_Config::getInstance()->data->viewer->columnWidth;
            } else {
                $col['width'] = 100;
            }

            // add removenewline flag if this is set in the config
            if (Daiquiri_Config::getInstance()->data->viewer->removeNewline) {
                $col['format'] = array('removeNewline' => true);
            }

            // append col to cols array
            $cols[] = $col;
        }

        return array('status' => 'ok', 'cols' => $cols);
    }

    /**
     * Returns the rows of the given table and database.
     * @param array $params get params of the request
     * @return array
     */
    public function rows(array $params = array()) {
        // get db and table from params
        if (empty($params['db']) || empty($params['table'])) {
            return array('status' => 'error');
        } else {
            $db = $params['db'];
            $table = $params['table'];
        }

        // set init table
        $this->getResource()->init($db, $table);

        // get columns from the database
        $colnames = array_keys($this->getResource()->fetchCols());

        // get the table from the resource
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);
        $rows = $this->getResource()->fetchRows($sqloptions);

        if (in_array('row_id', $colnames)) {
            $pk = 'row_id';
        } else {
            $pk = $this->getResource()->fetchPrimary();
        }

        return $this->getModelHelper('pagination')->response($rows,$sqloptions,$pk);
    }

    /**
     * Returns a pair of rows of the given table and database for plotting.
     * @param array $params get params of the request
     * @return array
     */
    public function plot(array $params = array()) {
        // get db and table from params
        if (empty($params['db']) || empty($params['table'])) {
            return array('status' => 'error');
        } else {
            $db = $params['db'];
            $table = $params['table'];
        }

        // set init table
        $this->getResource()->init($db, $table);

        // get columns from the database
        $colnames = array_keys($this->getResource()->fetchCols());

        // check if x and y are indeed in the database
        if (!in_array($params['x'],$colnames)) {
            return array(
                'status' => 'error',
                'errors' => array('plot_x' => array("Column `{$params['x']}` is not in the database table"))
            );
        }
        if (!in_array($params['y'],$colnames)) {
            return array(
                'status' => 'error',
                'errors' => array('plot_y' => array("Column `{$params['y']}` is not in the database table"))
            );
        }

        // get the table from the resource
        $rows = $this->getResource()->fetchPlot($params['x'],$params['y'],$params['nrows']);

        return array('status' => 'ok', 'rows' => $rows, 'x' => $params['x'], 'y' => $params['y']);
    }

}
