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

class Query_Form_SqlQuery extends Query_Form_AbstractFormQuery {

    /**
     * The default value for the query field.
     * @var string
     */
    protected $_query;

    /**
     * Sets $_query.
     * @param string $query the default value for the query field
     */
    public function setQuery($query) {
        $this->_query = $query;
    }

    /**
     * Gets the SQL query contructed from the form fields.
     * @return string $sql
     */
    public function getQuery() {
        return $this->getValue('sql_query');
    }

    /**
     * Gets the content of the tablename field.
     * @return string $tablename
     */
    public function getTablename() {
        return $this->getValue('sql_tablename');
    }

    /**
     * Gets the selected queue.
     * @return string $queue
     */
    public function getQueue() {
        return $this->getValue('sql_queues');
    }

    /**
     * Initializes the form.
     */
    public function init() {
        // add form elements
        $this->addCsrfElement('sql_csrf');

        $this->addNoteElement('sql_bar', '<div ng-controller="BarController">
            <div class="daiquiri-query-bar">
                <ul class="nav-pills pull-left">
                    <li ng-class="{\'active\': visible === \'databases\'}">
                        <a href="" ng-click="toogleDatabases()">Database browser</a>
                    </li>
                    <li ng-class="{\'active\': visible === \'functions\'}">
                        <a href="" ng-click="toogleFunctions()">Function browser</a>
                    </li>
                    <li ng-class="{\'active\': visible === \'simbad\'}">
                        <a href="" ng-click="toogleSimbad()">Simbad object search</a>
                    </li>
                </ul>
                <ul class="nav-pills pull-right">
                    <li ng-class="{\'active\': visible === \'examples\'}">
                        <a href="" ng-click="toogleExamples()">Examples</a>
                    </li>
                </ul>
            </div>
            <div ng-show="visible === \'databases\'">
                <div daiquiri-browser data-browser="databases"></div>
                <div class="daiquiri-query-bar-hint">
                    A double click will paste the database/table/column identifier into the query field.
                </div>
            </div>
            <div ng-show="visible === \'functions\'">
                <div class="span3">
                    <div daiquiri-browser data-browser="keywords"></div>
                </div>
                <div class="span3">
                    <div daiquiri-browser data-browser="nativeFunctions"></div>
                </div>
                <div class="span3">
                    <div daiquiri-browser data-browser="customFunctions"></div>
                </div>
                <div class="daiquiri-query-bar-hint">
                    A double click will paste the function into the query field.
                </div>
            </div>
            <div ng-show="visible === \'examples\'">
                <div>
                    <div daiquiri-browser data-browser="examples"></div>
                </div>
                <div class="daiquiri-query-bar-hint">
                    A double click will replace the content of the query field with the example query.
                </div>
            </div>
            <div ng-show="visible === \'simbad\'">
   
                <div id="simbad-resolver" ng-controller="simbadForm">
                  <table>
                    <tr>
                      <td><input type="text" name="simbad-identifier" id="simbad-input" ng-model="query" /></td>
                      <td><input type="button" value="Search on Simbad" class="btn btn-primary" id="simbad-submit" ng-click="simbadSearch()" /></td>
                    </tr>
                  </table>
                  <div id="simbad-results">
                     <table width="100%" ng-show="result.show" class="ng-hide">
                     <tr ng-repeat="item in result.data" class="item" ng-dblclick="$parent.browserItemDblClicked(\'coords\',item.coord1+\' \'+item.coord2)">
                        <td class="object">{{item.object}}</td>
                        <td class="type">{{item.type}}</td>
                        <td class="coords">{{item.coord1}} &nbsp; {{item.coord2}}</td>
                        <td width="100%"> </td>
                     </tr>
                     <tr ng-show="result.data.length==0" class="ng-hide"><td>No results for "{{result.query}}"</td></tr>
                     </table>
                  </div>                      
                </div>  
                <div class="daiquiri-query-bar-hint">
                    A double click on an item will copy the corresponding coordinates into the query.
                </div>
            </div>
        </div>');

        $this->addTextareaElement('sql_query', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'class' => 'span9 codemirror',
            'style' => "resize: none;",
            'rows' => 8
        ));

        $this->addTablenameElement('sql_tablename');
        $this->addSubmitButtonElement('sql_submit','Submit new SQL Query');
        $this->addClearInputButtonElement('sql_clear','Clear input window');
        $this->addQueuesElement('sql_queues');

        // add display groups
        $this->addInlineGroup(array('sql_bar'), 'sql-bar-group');
        $this->addSimpleGroup(array('sql_query'), 'sql-input-group');

        $this->addSimpleGroup(array('sql_tablename'), 'sql-table-group', false, true);

        $this->addQueuesGroup(array('sql_queues'), 'sql-queues-group');
        $this->addInlineGroup(array('sql_submit','sql_clear'), 'sql-button-group');

        // angularify form
        $this->addAngularDecorators('sql',array('sql_query','sql_tablename','sql_queues'));
    }

}
