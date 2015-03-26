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

        // get columns from params or from the database
        if (empty($params['cols'])) {
            $colnames = array_keys($this->getResource()->fetchCols());
        } else {
            // we can not use explode here since there can be commas in functions
            $lexer = new PHPSQLParser\lexer\PHPSQLLexer();
            $string = '';
            $colnames = array();
            foreach($lexer->split($params['cols']) as $token) {
                if ($token !== ',') {
                    $string .= $token;
                } else {
                    $colnames[] = $string;
                    $string = '';
                }
            }
            $colnames[] = $string;
        }

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

        // check if all colums are in the database
        if (count(array_intersect($colnames,array_keys($meta))) != count($colnames)) {
            throw new Exception('Some Columns are not in the database table');
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

        // get columns from params or from the database
        if (empty($params['cols'])) {
            $colnames = array_keys($this->getResource()->fetchCols());
        } else {
            // we can not use explode here since there can be commas in functions
            $lexer = new PHPSQLParser\lexer\PHPSQLLexer();
            $string = '';
            $colnames = array();
            foreach($lexer->split($params['cols']) as $token) {
                if ($token !== ',') {
                    $string .= $token;
                } else {
                    $colnames[] = $string;
                    $string = '';
                }
            }
            $colnames[] = $string;
        }

        // get the table from the resource
        $sqloptions = $this->getModelHelper('pagination')->sqloptions($params);
        $dbRows = $this->getResource()->fetchRows($sqloptions);

        // filter rows
        $rows = array();
        foreach($dbRows as $dbRow) {
            $row = array();
            foreach($colnames as $colname) {
                $row[$colname] = $dbRow[$colname];
            }
            $rows[] = $row;
        }

        if (in_array('row_id', $colnames)) {
            $pk = 'row_id';
        } else {
            $pk = $this->getResource()->fetchPrimary();
        }

        return $this->getModelHelper('pagination')->response($rows,$sqloptions,$pk);
    }

}