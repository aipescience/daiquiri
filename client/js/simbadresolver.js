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

var app= angular.module('simbadResolver',['browser']);

/*
app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.controller('ViewerController', ['$scope','tableService',function($scope,tableService) {
    
    tableService.url.cols = '/data/viewer/cols?db=daiquiri_user_admin&table=100';
    tableService.url.rows = '/data/viewer/rows?db=daiquiri_user_admin&table=100';

    tableService.init();
}]);
*/

app.factory('SimbadParser',['$http',function ($http) {
 
  function simbadSearch(query,callBack) {

    query = query.replace(" ","+");
    
    var url = "http://simbad.u-strasbg.fr/simbad/sim-id?Ident="+query+"&output.format=votable&output.params=main_id,coo(d),otype(V)"
    
    $http({
       method: 'GET',
       url: url
    }).then(function successCallback(response) {
       xmlDoc = $.parseXML(response.data);
       $xml = $(xmlDoc);
       rows = $xml.find('TABLEDATA TR')
       data = []
       rows.each(function(i){
         cols = $(this).find('TD');
         data[i] = {
           object: cols.eq(0).text(),
           type: cols.eq(6).text(),
           coord1: cols.eq(1).text(),
           coord2: cols.eq(2).text(),
         }
       });
       callBack(data);
    }, function errorCallback(response) {
       alert(response.data);
    });

  }
 
  return {
    simbadSearch: simbadSearch,
  };

}]);

app.controller('simbadForm', ['$scope','SimbadParser',function ($scope,SimbadParser) {

  $scope.query = 'cancer';
  $scope.result = {cols:"",
                   show:false
                  };

  $scope.simbadSearch = function () {  
    SimbadParser.simbadSearch($scope.query,function(data){
      $scope.result.data = data;
      $scope.result.query = $scope.query;
      $scope.result.show = true;
    });
  }

  $scope.browserItemDblClicked = function(browsername,coords) {
      $scope.$emit('browserItemDblClicked',browsername,coords);
  };

}]);

