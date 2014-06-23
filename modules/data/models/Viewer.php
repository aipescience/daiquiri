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

        // return columns ot this table
        $cols = array();
        foreach ($colnames as $colname) {
            $col = array(
                'id' => $meta[$colname]['id'],
                'name' => $colname,
                'sortable' => true
            );

            if ($colname === 'row_id') {
                $col['width'] = '4em';
                $col['hidden'] = true;

            } else if (in_array('meta.ref', $meta[$colname]['ucd'])) {
                // this is a link, it needs more space
                $col['width'] = '20em';

                // is this a file daiquiri hosts or just a link?
                if (in_array('meta.file', $meta[$colname]['ucd']) || in_array('meta.fits', $meta[$colname]['ucd'])) {
                    // this is a file we host and can be downloaded
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    $col['format'] = array(
                        'type' => 'filelink',
                        'base' => $baseurl . '/data/files/single',
                    );
                } else {
                    // we just show this as a link - meta.ref.url also ends up here
                    $col['format'] = array(
                        'type' => 'link',
                        'target' => 'blank'
                    );
                }
                // TODO treat uri and ivorn different
            } else {
                // regular column, take the with from the config or a default one
                $width = Daiquiri_Config::getInstance()->data->viewer->columnWidth;
                if (empty($width)) {
                    $col['width'] = '12em';
                } else {
                    $col['width'] = $width;
                }

                // add removenewline flag if this is set in the config
                if (Daiquiri_Config::getInstance()->data->viewer->removeNewline) {
                    $col['format'] = array('removeNewline' => true);
                }
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