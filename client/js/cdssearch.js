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

angular.module('cdsSearch',['browser','query'])

// Factory for Simbad queries
.factory('CdsSearchService',['$http','QueryService',function ($http,QueryService) {

    // query options, will be set inside the template via ng-init
    var options = {};

    function init(opt) {
        options = opt;
    }

    // Search results
    var simbadResults = {
      query: '',
      data: false,
      coordOutput: true  // are coordinates clickable
    };
    var vizierResults = {
      query: '',
      data: false,
      idOutput: true  // is ID clickable
    };

    function resetResults() {
        simbadResults.query = '';
        simbadResults.data = false;
        simbadResults.coordOutput = true;
        vizierResults.query = '';
        vizierResults.data = false;
        vizierResults.idOutput = true;
    }

    // Search on Simbad and parse the data
    function simbadSearch(query,callback) {

        simbadResults.query = query;

        /*
         * Simbad query URL
         * more information and settings at:
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-url
         * http://simbad.u-strasbg.fr/simbad/sim-help?Page=sim-fscript#VotableFields
         */
        var opts = {
            "output.format": "votable",
            "output.params": "main_id,coo(d),otype(V)",
            "Ident": simbadResults.query
        };

        // get and parse the XML VOTable
        $http({
           method: 'GET',
           url: options.simbadSearchUrl + "simbad/sim-id?" + $.param(opts)
        }).then(function successCallback(response) {
            xmlDoc = $.parseXML(response.data);
            $xml = $(xmlDoc);
            rows = $xml.find('TABLEDATA TR');
            simbadResults.data = [];
            rows.each(function(i) {
                cols = $(this).find('TD');
                simbadResults.data[i] = {
                    object: cols.eq(0).text(),
                    type: cols.eq(6).text(),
                    coord1: cols.eq(1).text(),
                    coord2: cols.eq(2).text(),
                };
            });

            if (angular.isDefined(QueryService.dialog.options)) {
                if (QueryService.dialog.options.coordOutput === false) {
                    simbadResults.coordOutput = false;
                }
            }

            callback();
        }, function errorCallback(response) {
            console.log('Error: results were not resolved from simbad');
        });

    }

    // Search on VizieR and parse the data
    function vizierSearch(coords,callback) {

        vizierResults.query = coords;
        var catalogs = options.vizierSearchCatalogs;

        /*
         * VizieR query URL
         * more information and settings at:
         * http://vizier.u-strasbg.fr/doc/asu-summary.htx (ASU qualifications for VizieR)
         * http://vizier.u-strasbg.fr/viz-bin/vizHelp?cats/U.htx (CDS Catalogues list)
         * More catalogs can be set in Daiquiri "Admin>Configuration" by editing of "query.simbadSearch.vizier.catalogs.X"
         * Use the VizieR catalog identifiers (i.e. I/322A, J/MNRAS/416/2265,...) from the CDS Catalogue list.
         */
        var opts = {
                "-source": catalogs.join(" "),
                "-c": vizierResults.query,
                "-c.r": 2,
                "-out": "_RA _DEC _r *meta.id.part;meta.main *meta.id;meta.main",
                "-sort": "_r",
                "-out.max": 5
            };

        // get and parse the XML VOTable
        $http({
           method: 'GET',
           url: options.vizierSearchUrl+"viz-bin/votable?"+$.param(opts)
        }).then(function successCallback(response) {
            xmlDoc = $.parseXML(response.data);
            $xml = $(xmlDoc);

            vizierResults.data = {'results':{},'show':false};
            for (var c in catalogs) {
                var $cxml = $xml.find("RESOURCE[name='"+catalogs[c]+"']");

                $cxml.find("TABLEDATA TR").each(function() {
                  cols = $(this).find('TD');
                  if (catalogs[c]=='I/259') { // exception for Tycho 2 catalog
                      id = cols.eq(3).text()+"-"+cols.eq(4).text()+"-"+cols.eq(5).text();
                  } else {
                      id = cols.eq(3).text();
                  }
                  r = cols.eq(2).text();
                  vizierResults.data['results'][r+'_'+c] = {
                      id: id,
                      ra: cols.eq(0).text(),
                      dec: cols.eq(1).text(),
                      r: cols.eq(2).text(),
                      catalog: $cxml.find("DESCRIPTION:first").text()
                  };
                  vizierResults.data['show'] = true;
                });
            }

            if (angular.isDefined(QueryService.dialog.options)) {
                if (QueryService.dialog.options.idOutput === false) {
                    vizierResults.idOutput = false;
                }
            }

            callback();
        }, function errorCallback(response) {
            console.log('Error: results were not resolved from VizieR');
        });

    }

    return {
        init: init,
        simbadSearch: simbadSearch,
        simbadResults: simbadResults,
        vizierSearch: vizierSearch,
        vizierResults: vizierResults,
        resetResults: resetResults,
    };
}])

//Controller for a Simbad search form
.controller('CdsSearchController', ['$scope','$rootScope','CdsSearchService','ModalService','QueryService',function ($scope, $rootScope, CdsSearchService,ModalService,QueryService) {

    $scope.simbadQuery = '';
    $scope.simbad = CdsSearchService.simbadResults;

    $scope.vizierQuery = '';
    $scope.vizier = CdsSearchService.vizierResults;

    // Perform a Simbad search and get the data
    $scope.simbadSearch = function () {
        if ($scope.simbadQuery !== "") {
            CdsSearchService.simbadSearch($scope.simbadQuery,function(){
            });
        }
    };

    // Perform a Simbad search and get the data
    $scope.vizierSearch = function (callBack) {
        if ($scope.vizierQuery !== "") {
            CdsSearchService.vizierSearch($scope.vizierQuery,function(){
                if (typeof callBack === "function") {
                    callBack();
                }
            });
        }
    };

    // Search IDs in VizieR catalogues
    $scope.showCatalogIds = function(coord1,coord2) {
        $scope.vizierQuery = coord1+' '+coord2;
        $scope.vizierSearch(function(){
            $('#vizierTabButton').tab('show');
        });
    };

    // Overide an enter/return stoke in the input field
    $scope.simbadInput = function (event) {
        if (event.keyCode === 13) {
            $scope.simbadSearch();
            event.preventDefault();
            event.stopPropagation();
        }
    };

    // Overide an enter/return stoke in the input field
    $scope.vizierInput = function (event) {
        if (event.keyCode === 13) {
            $scope.vizierSearch();
            event.preventDefault();
            event.stopPropagation();
        }
    };

    // Paste coordinates
    $scope.pasteCoordinates = function(coord1,coord2) {
        if (angular.isUndefined(QueryService.dialog.options)) {
            $rootScope.$broadcast('browserItemDblClicked','cdssearch',coord1+' '+coord2);
        } else {
            $('#'+QueryService.dialog.options.coordOutput[0]).val(coord1);
            $('#'+QueryService.dialog.options.coordOutput[0]).trigger('input');
            $('#'+QueryService.dialog.options.coordOutput[1]).val(coord2);
            $('#'+QueryService.dialog.options.coordOutput[1]).trigger('input');
        }
        ModalService.close();
    };

    // Paste IDs
    $scope.pasteIDs = function(id) {
        if (angular.isUndefined(QueryService.dialog.options)) {
            $rootScope.$broadcast('browserItemDblClicked','cdssearch',id);
        } else {
            $('#'+QueryService.dialog.options.idOutput).val(id);
            $('#'+QueryService.dialog.options.idOutput).trigger('input');
        }
        ModalService.close();
    };

}]);
