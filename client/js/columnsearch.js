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

// Search columns feature
angular.module('columnSearch',['browser'])

.factory('ColumnSearchService',['$http',function ($http) {

    // Returns a list of columns that match the searched query
    function searchInData(data,query,callBack) {

        // Escape all RegExp special characters and split the query
        query = query.replace(/[-[\]{}()*+?.,\\^$|#]/g, "\\$&");
        query = query.split(/\s+/ig);

        searchResults = [];
        i = 0;

        // Search in all available columns
        for (d=0; d<data.databases.length; d++) {
            for (t=0; t<data.databases[d].tables.length; t++) {
                for (c=0; c<data.databases[d].tables[t].columns.length; c++) {

                    // Preparing a string to be searched in
                    var string = data.databases[d].tables[t].columns[c].tooltip;
                    if (angular.isDefined(string)) {
                        string = string.replace(/(\<br \/\>|)\<i\>(Type|UCD|Unit):\<\/i\>/ig ,' '); // removing the titles
                        string = data.databases[d].name +  " " +
                            data.databases[d].tables[t].name + " " +
                            data.databases[d].tables[t].columns[c].name + " " + string;

                        // Search each subquery in the string
                        var found = true;
                        for (q=0; q<query.length; q++) {
                            if (string.search(new RegExp(query[q],"i"))<0) {
                                found = false;
                            }
                        }

                        // Append list if each subquery was found in the string
                        if (found) {
                            searchResults[i] = {
                                database: data.databases[d].name,
                                table: data.databases[d].tables[t].name,
                                column: data.databases[d].tables[t].columns[c].name,
                                tooltip: data.databases[d].tables[t].columns[c].tooltip
                            };
                            i++;
                        }
                    }
                }
            }
        }

        callBack(searchResults);

    }

    return {
        searchInData: searchInData
    };
}])

// Controller for a column search form
.controller('ColumnSearchController', ['$scope','ColumnSearchService',function ($scope, ColumnSearchService) {

    $scope.query = ''; // contains a input field string
    $scope.result = {cols:'', show:false}; // results of the search

    // Overide an enter/return stoke in the input field
    $scope.columnInput = function (event) {
        if (event.keyCode === 13) {
            $scope.columnSearch();
            event.preventDefault();
            event.stopPropagation();
        }
    };

    // Perform a column search and get the data
    $scope.columnSearch = function () {
        if ($scope.query !== "") {
            ColumnSearchService.searchInData($scope.$parent.databases.data, $scope.query, function(data){
                $scope.result.data = data;
                $scope.result.query = $scope.query;
                $scope.result.show = true;
            });
        }
    };

    // Insert database/table/column name into the query text field
    // event is handled in 'query.js' file
    $scope.browserItemDblClicked = function(string) {
      $scope.$emit('browserItemDblClicked','columnSearch',"`"+string+"`");
    };

    // Display datailed info about the column in the right panel
    $scope.browserItemClicked = function(item) {
      $('#column-search-tooltip').html("<strong>"+item.column+"</strong> "+item.tooltip);
    };

}]);
