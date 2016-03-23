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
.factory('SearchService',['$http',function ($http) {
    // query options, will be set inside the template via ng-init
    var options = {};

    function init(opt) {
        options = opt;
    }

    // Search on Simbad and parse the data
    function simbadSearch(query,callBack) {

        query = encodeURIComponent(query);
        
        /*
         * Simbad query URL
         * more information and settings at:
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-url
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-fscript#VotableFields
         */
        var opts = {"output.format": "votable",
                    "output.params": "main_id,coo(d),otype(V)",
                    "Ident": query}
        
        // get and parse the XML VOTable
        $http({
           method: 'GET',
           url: options.simbadSearchUrl + "simbad/sim-id?" + $.param(opts)
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
            console.log('Error: results were not resolved from simbad');
        });
    }

    // Search on VizieR and parse the data
    function vizierSearch(coord1,coord2,callBack) {

        var catalogs = options.vizierSearchCatalogs

        /*
         * VizieR query URL
         * more information and settings at:
         * http://vizier.u-strasbg.fr/doc/asu-summary.htx (ASU qualifications for VizieR)
         * http://vizier.u-strasbg.fr/viz-bin/vizHelp?cats/U.htx (CDS Catalogues list)
         * More catalogs can be set in Daiquiri "Admin>Configuration" by editing of "query.simbadSearch.vizier.catalogs.X"
         * Use the VizieR catalog identifiers (i.e. I/322A, J/MNRAS/416/2265,...) from the CDS Catalogue list.
         */
        var opts = {"-source":catalogs.join(" "),
                "-c": coord1+" "+coord2,
                "-c.r":2,
                "-out":"_RA _DEC _r *meta.id.part;meta.main *meta.id;meta.main",
                "-sort":"_r",
                "-out.max":5}
       

        // get and parse the XML VOTable
        $http({
           method: 'GET',
           url: options.vizierSearchUrl+"viz-bin/votable?"+$.param(opts)
        }).then(function successCallback(response) {
            xmlDoc = $.parseXML(response.data);
            $xml = $(xmlDoc);

            var data = {}
            for (c in catalogs) {
                var $cxml = $xml.find("RESOURCE[name='"+catalogs[c]+"']")
                data[c] = {"name": $cxml.find("DESCRIPTION:first").text(),
                           "id": catalogs[c],
                           "data": []}
                $cxml.find("TABLEDATA TR").each(function(i) {
                  cols = $(this).find('TD');
                  if (catalogs[c]=='I/259') { // exception for Tycho 2 catalog
                      id = cols.eq(3).text()+"-"+cols.eq(4).text()+"-"+cols.eq(5).text()
                  } else {
                      id = cols.eq(3).text()
                  }
                  data[c]['data'][i] = {
                      id: id,
                      ra: cols.eq(0).text(),
                      dec: cols.eq(1).text(),
                      r: cols.eq(2).text(),
                  };
                });
            }

            callBack(data);
        }, function errorCallback(response) {
            console.log('Error: results were not resolved from VizieR');
        });

    }

    return {
        init: init,
        simbadSearch: simbadSearch,
        vizierSearch: vizierSearch
    };
}])

//Controller for a Simbad search form
.controller('SimbadSearchController', ['$scope','SearchService','ModalService',function ($scope, SearchService,ModalService) {

    $scope.query = ''; // contains a input field string
    $scope.result = {cols:"", show:false}; // contains results from simbad

    $scope.vizierResults = {};
    $scope.vizierCenter = {'ra':0,'dec':0};

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
            SearchService.simbadSearch($scope.query,function(data){
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

    // Search for the Catalog ID using the VizieR
    $scope.vizierSearch = function (coord1,coord2) {
        if (coord1 !== "") {
            SearchService.vizierSearch(coord1,coord2,function(data){
                $scope.vizierResults = data;
                $scope.vizierCenter = {'ra':coord1,'dec':coord2}
                ModalService.open();
            });
        } else {
            alert('No coordinates available.');
        }
    }

    // Insert ID into the query text field
    // event is handled in 'query.js' file
    $scope.inputCatalogIdIntoQuery = function (id) {
        $scope.$emit('browserItemDblClicked','coords',id);
        ModalService.close();
    }

    // Insert ID into the "light curve" text field
    $scope.inputCatalogIdIntoSlc = function (id) {
        $('#scl_id').val(id)
        $('#scl_id').trigger('input');
        ModalService.close();
    }

}]);
