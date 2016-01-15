/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>,
 *                           Ondrej Jaura <ojaura@aip.de>,
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

angular.module('columnSearch',['browser'])

.factory('SearchService',['$http',function ($http) {

    function parseTooltip(tooltip) {
      desc = tooltip.match(/\b([^\<]*)/i);
      type = tooltip.match(/\<i\>Type:\<\/i\>([^\<]*)/i);
      ucd = tooltip.match(/\<i\>UCD:\<\/i\>([^\<]*)/i);
      unit = tooltip.match(/\<i\>Unit:\<\/i\>([^\<]*)/i);
      return {
        "desc": desc==null ? "" : desc[1],
        "type": type==null ? "" : type[1],
        "ucd": ucd==null ? "" : ucd[1],
        "unit": unit==null ? "" : unit[1],
      }
    }

    function searchInData(data,query,callBack) {
      //console.log(data);
      searchResults = []
      i = 0;
      for (d=0; d<data.databases.length; d++) {
        for (t=0; t<data.databases[d].tables.length; t++) {
          for (c=0; c<data.databases[d].tables[t].columns.length; c++) {
            var string = data.databases[d].tables[t].columns[c].tooltip;
            string = string.replace( /(\<br \/\>|)\<i\>(Type|UCD|Unit):\<\/i\>/ig ,' ');
            //string = string.replace( /\<br \/\>/ig ,' ');
            string = data.databases[d].tables[t].columns[c].name + " " + string;
            //console.log(string)
            if (string.search(query)>=0) {
              var tooltip = parseTooltip( data.databases[d].tables[t].columns[c].tooltip );
              searchResults[i] = {
                database: data.databases[d].name,
                table: data.databases[d].tables[t].name,
                column: data.databases[d].tables[t].columns[c].name,
                description: tooltip.desc,
                type: tooltip.type,
                ucd: tooltip.ucd,
                unit: tooltip.unit
              }
              i++;
            }
          }
        }
      }
      console.log(searchResults)
      callBack(searchResults);
    }

    return {
        searchInData: searchInData
    };
}])

//Controller for a Simbad search form
.controller('columnSearchForm', ['$scope','SearchService',function ($scope,SearchService) {

    $scope.query = ''; // contains a input field string
    $scope.result = {cols:"",show:false}; // contains results from simbad

    // Overide an enter/return stoke in the input field
    $scope.simbadInput = function (event) {
        if (event.keyCode === 13) {
            $scope.simbadSearch();
            event.preventDefault();
            event.stopPropagation();
        }
    };

    // Perform a Column Search and get the data
    $scope.columnSearch = function () {
        if ($scope.query !== "") {
            console.log($scope.$parent.databases);
            SearchService.searchInData($scope.$parent.databases.data, $scope.query, function(data){
                console.log(data);
                $scope.result.data = data;
                $scope.result.query = $scope.query;
                $scope.result.show = true;
            });
        }
    };

    // Insert column name into the query text field
    // event is handled in 'query.js' file
    $scope.browserItemDblClicked = function(database,table,column) {
        if (column !== "") {
            $scope.$emit('browserItemDblClicked','columnSearch',"`"+database+"`.`"+table+"`.`"+column+"`");
        } else {
            alert('No coordinates available.');
        }
    };
}]);
