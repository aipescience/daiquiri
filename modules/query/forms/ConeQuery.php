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

class Query_Form_ConeQuery extends Query_Form_AbstractFormQuery {

    /**
     * Gets the SQL query contructed from the form fields.
     * @return string $sql
     */
    public function getQuery() {
        if (!isset($this->_formOptions['table'])) {
            throw new Exception('no table was specified');
        }
        if (!isset($this->_formOptions['raField'])) {
            throw new Exception('no ra field was specified');
        }
        if (!isset($this->_formOptions['decField'])) {
            throw new Exception('no dec field was specified');
        }

        $ra     = $this->_escape($this->getValue('cone_ra'));
        $dec    = $this->_escape($this->getValue('cone_dec'));
        $radius = $this->_escape($this->getValue('cone_radius'));

        $sql = "SELECT angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " * 3600.0 AS distance_arcsec, s.* FROM {$this->_formOptions['table']} AS s";
        $sql .= " WHERE angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " < {$radius} / 3600.0;";

        return $sql;
    }

    /**
     * Gets the content of the tablename field.
     * @return string $tablename
     */
    public function getTablename() {
        return $this->getValue('cone_tablename');
    }

    /**
     * Gets the selected queue.
     * @return string $queue
     */
    public function getQueue() {
        return $this->getValue('cone_queues');
    }

    /**
     * Initializes the form.
     */
    public function init() {

        $html = '<div class="daiquiri-query-bar" style="width:145px;">
                   <ul class="nav-pills pull-left">
                      <li><a ng-click="showSimbadSearch = !showSimbadSearch" href style="width:121px;">{{showSimbadSearch?"Hide Simbad search":"Show Simbad search"}}</a></li>
                   </ul>
                 </div>
                 <div ng-show="showSimbadSearch" ng-init="showSimbadSearch = false">
                  <div id="simbad-resolver" ng-controller="simbadForm">
                    <div id="simbad-form">
                        <input type="text" name="simbad-identifier" id="simbad-input" ng-model="query"
                               ng-keydown="simbadInput($event);" />
                        <input type="button" value="Search on Simbad" class="btn pull-right" id="simbad-submit" ng-click="simbadSearch()" />
                    </div>

                    <ul id="simbad-results" class="daiquiri-widget nav nav-pills nav-stacked">
                        <li ng-repeat="item in result.data" class="nav-item" ng-dblclick="$parent.inputSourceConeSearch(item.coord1,item.coord2)">
                              <a href="">
                                  <div class="object">{{item.object}}</div>
                                  <div class="type">{{item.type}}</div>
                                  <div class="coords">{{item.coord1}} &nbsp; {{item.coord2}}</div>
                              </a>
                        </li>
                        <li ng-show="result.data.length==0" class="simbad-results-empty">
                            No results for "{{result.query}}"
                        </li>
                    </ul>
                  </div>
                  <div class="daiquiri-query-bar-hint">
                      A double click on an item will copy the corresponding coordinates into the following form fields.
                  </div>
                </div>';
        $this->addNoteElement('cone_simbad', $html);


        // add form elements
        $this->addCsrfElement('cone_csrf');
        $this->addFloatElement('cone_ra', 'RA<sub>deg</sub>');
        $this->addFloatElement('cone_dec', 'DEC<sub>deg</sub>');
        $this->addFloatElement('cone_radius', 'Radius<sub>arcsec</sub>');
        $this->addTablenameElement('cone_tablename');
        $this->addSubmitButtonElement('cone_submit', 'Submit new cone search');
        $this->addQueuesElement('cone_queues');

        // add display groups
        $this->addHorizontalGroup(array('cone_ra','cone_dec','cone_radius'), 'cone-values-group');
        $this->addSimpleGroup(array('cone_tablename'), 'cone-table-group', false, true);

        $this->addQueuesGroup(array('cone_queues'), 'cone-queues-group');
        $this->addInlineGroup(array('cone_submit'), 'cone-button-group');

        // fill elements with default values
        $this->setDefault('cone_ra', $this->_formOptions['raDefault']);
        $this->setDefault('cone_dec', $this->_formOptions['decDefault']);
        $this->setDefault('cone_radius', $this->_formOptions['radiusDefault']);

        // angularify form
        $this->addAngularDecorators('cone',array('cone_ra','cone_dec','cone_radius','cone_tablename','cone_queues'));
    }

}
