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

angular.module('simbadSearch',['browser'])

// Factory for Simbad queries
.factory('SimbadSearchService',['$http',function ($http) {
    // query options, will be set inside the template via ng-init
    var options = {};

    function init(opt) {
        options = opt;
    }

    // Search on Simbad and parse the data
    function simbadSearch(query,callBack) {

        query = encodeURIComponent(query);
        //query = query.replace("+","%2B");
        //query = query.replace(" ","%20");
        
        /*
         * Simbad query URL
         * more information and settings at:
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-url
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-fscript#VotableFields
         */
        var url = options.simbadSearchUrl + "/simbad/sim-id?Ident="+query+"&output.format=votable&output.params=main_id,coo(d),otype(V)";

        // get and parse the XML VOTable
        $http({
           method: 'GET',
           url: url
        }).then(function successCallback(response) {
            xmlDoc = $.parseXML(response.data);
            $xml = $(xmlDoc);
            rows = $xml.find('TABLEDATA TR');
            data = [];
            rows.each(function(i) {
                cols = $(this).find('TD');
                data[i] = {
                    object: cols.eq(0).text(),
                    type: cols.eq(6).text(),
                    coord1: cols.eq(1).text(),
                    coord2: cols.eq(2).text(),
                };
            });
            callBack(data);
        }, function errorCallback(response) {
            alert(response.data);
        });
    }

    return {
        init: init,
        simbadSearch: simbadSearch
    };
}])

//Controller for a Simbad search form
.controller('SimbadSearchController', ['$scope','SimbadSearchService',function ($scope, SimbadSearchService) {

    $scope.query = ''; // contains a input field string
    $scope.result = {cols:"", show:false}; // contains results from simbad

    // Overide an enter/return stoke in the input field
    $scope.simbadInput = function (event) {
        if (event.keyCode === 13) {
            $scope.simbadSearch();
            event.preventDefault();
            event.stopPropagation();
        }
    };

    // Perform a Simbad search and get the data
    $scope.simbadSearch = function () {
        if ($scope.query !== "") {
            SimbadSearchService.simbadSearch($scope.query,function(data){
                $scope.result.data = data;
                $scope.result.query = $scope.query;
                $scope.result.show = true;
            });
        }
    };

    // Insert coordinates into the query text field
    // event is handled in 'query.js' file
    $scope.browserItemDblClicked = function(browsername,coord1,coord2) {
        if (coord1 !== "") {
            $scope.$emit('browserItemDblClicked',browsername,coord1+' '+coord2);
        } else {
            alert('No coordinates available.');
        }
    };

    $scope.inputPlateConeSearch = function(coord1,coord2) {
        $('#plate_racent').val(coord1);
        $('#plate_racent').trigger('input');
        $('#plate_decent').val(coord2);
        $('#plate_decent').trigger('input');    
    };

    $scope.inputSourceConeSearch = function(coord1,coord2) {
        $('#cone_ra').val(coord1);
        $('#cone_ra').trigger('input');
        $('#cone_dec').val(coord2)
        $('#cone_dec').trigger('input');
    };
}]);
