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

angular.module('browser',[])

.directive('daiquiriBrowser', ['BrowserService',function(BrowserService) {
    return {
        templateUrl: '/daiquiri/html/browser.html',
        scope: {
            browser: '='
        }
    };
}])

.factory('BrowserService', ['$http',function($http) {
    var height = 200;
    var browser = {};

    function getItems(data) {
        var items = [];
        angular.forEach(data, function(item, key) {
            items.push({
                'id': item.id,
                'name': item.name,
                'order': item.order
            })
        });
        return items;
    }

    function updateBrowser(name, colname, item, i) {
        var colname0 = browser[name].colnames[0];
        var colname1 = browser[name].colnames[1];
        var colname2 = browser[name].colnames[2];

        // the FIRST column was clicked
        if (colname === colname0 && !angular.isUndefined(colname1) && i != browser[name].cols[0].selected) {
            browser[name].cols[0].selected = i;
            browser[name].cols[1].selected = 0;

            // update SECOND column
            browser[name].cols[1].items = getItems(browser[name].data[colname0][i][colname1]);

            // update THIRD column
            if (!angular.isUndefined(colname2)) {
                browser[name].cols[2].items = getItems(browser[name].data[colname0][i][colname1][0][colname2]);
            }
        }

        // the SECOND column was clicked
        if (colname === colname1 && !angular.isUndefined(colname2) && i != browser[name].cols[1].selected) {
            var active0 = browser[name].cols[0].selected;
            browser[name].cols[1].selected = i;

            // update THIRD column
            browser[name].cols[2].items = getItems(browser[name].data[colname0][active0][colname1][i][colname2]);
        }
    };

    function initBrowser(name) {
        browser[name].name = name;
        browser[name].cols = [];

        // with of one column of the browser
        var width = Math.floor(220 + (1 - 1 / browser[name].colnames.length) * 20 - 1);

        $http.get(browser[name].url).success(function(response) {
            if (response.status == 'ok') {
                browser[name].data = response;

                for (var i=0; i<browser[name].colnames.length; i++) {
                    var colname = browser[name].colnames[i];
                    if (!angular.isUndefined(colname)) {
                        browser[name].cols.push({
                            'id': i,
                            'name': colname,
                            'height': height,
                            'width': width,
                            'items': []
                        });
                    }
                }

                var colname0 = browser[name].colnames[0];
                var colname1 = browser[name].colnames[1];
                var colname2 = browser[name].colnames[2];

                // init FIRST column
                if (!angular.isUndefined(colname0)) {
                    browser[name].cols[0].items = getItems(browser[name].data[colname0]);
                }

                // init SECOND column
                if (!angular.isUndefined(colname1)) {
                    browser[name].cols[1].items = getItems(browser[name].data[colname0][0][colname1]);
                    browser[name].cols[0].selected = 0;
                }

                // init THIRD column
                if (!angular.isUndefined(colname2)) {
                    browser[name].cols[2].items = getItems(browser[name].data[colname0][0][colname1][0][colname2]);
                    browser[name].cols[1].selected = 0;
                }

            } else {
                console.log('Error');
            }
        });
    };

    return {
        browser: browser,
        initBrowser: initBrowser,
        updateBrowser: updateBrowser
    };
}])

.controller('BrowserController', ['$scope','BrowserService',function($scope,BrowserService) {

    $scope.browserItemClicked = function(browsername,colname,item,i) {
        BrowserService.updateBrowser(browsername,colname,item,i);
        $scope.$emit('browserItemClicked',browsername,colname,item.id);
    };

    $scope.$on('browserItemActive', function(event,browsername,colname,id) {
        $scope.browser.active = {
            'browsername': browsername,
            'colname': colname,
            'id': id
        }
    });

}]);
