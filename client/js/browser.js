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

.factory('BrowserService', ['$http','$window',function($http,$window) {
    var height = 200;
    var browser = {};

    function getItems(data) {
        var items = [];
        angular.forEach(data, function(item, key) {
            items.push({
                'id': item['id'],
                'order': item.order,
                'name': item.name
            })
        });
        return items;
    }

    function updateBrowser(browserId, colId, itemId) {
        var key0 = browser[browserId].keys[0];
        var key1 = browser[browserId].keys[1];
        var key2 = browser[browserId].keys[2];

        // the FIRST column was clicked
        if (colId == 0 && !angular.isUndefined(key0) && !angular.isUndefined(key1)) {
            // update active item
            if (itemId != browser[browserId].cols[0].selected) {
                browser[browserId].cols[0].selected = itemId;
                browser[browserId].cols[1].selected = 0;

                // update SECOND column
                browser[browserId].cols[1].items = getItems(browser[browserId].data[key0][itemId][key1]);


                // update THIRD column
                if (!angular.isUndefined(key2)) {
                    browser[browserId].cols[2].items = getItems(browser[browserId].data[key0][itemId][key1][0][key2]);
                }
            }
        }

        if (colId == 1 && !angular.isUndefined(key2)) {
            // update active item
            if (itemId != browser[browserId].cols[0].selected) {
                var active0 = browser[browserId].cols[0].selected;
                browser[browserId].cols[1].selected = itemId;

                // update THIRD column
                browser[browserId].cols[2].items = getItems(browser[browserId].data[key0][active0][key1][itemId][key2]);
            }
        }
    };

    function initBrowser(browserId) {
        browser[browserId].id = browserId;
        browser[browserId].cols = [];

        // with of one column of the browser
        var width = Math.floor(220 + (1 - 1 / browser[browserId].keys.length) * 20 - 1);

        $http.get(browser[browserId].url).success(function(response) {
            if (response.status == 'ok') {
                browser[browserId].data = response;

                for (var i=0; i<browser[browserId].keys.length; i++) {
                    var key = browser[browserId].keys[i];
                    if (!angular.isUndefined(key)) {
                        browser[browserId].cols.push({
                            'id': i,
                            'name': key,
                            'height': height,
                            'width': width,
                            'items': []
                        });
                    }
                }

                var key0 = browser[browserId].keys[0];
                var key1 = browser[browserId].keys[1];
                var key2 = browser[browserId].keys[2];

                // init FIRST column
                if (!angular.isUndefined(key0)) {
                    browser[browserId].cols[0].items = getItems(browser[browserId].data[key0]);
                }

                // init SECOND column
                if (!angular.isUndefined(key1)) {
                    browser[browserId].cols[1].items = getItems(browser[browserId].data[key0][0][key1]);
                    browser[browserId].cols[0].selected = 0;
                }

                // init THIRD column
                if (!angular.isUndefined(key2)) {
                    browser[browserId].cols[2].items = getItems(browser[browserId].data[key0][0][key1][0][key2]);
                    browser[browserId].cols[1].selected = 0;
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

    $scope.updateBrowser = function(browserId,colId,itemId) {
        BrowserService.updateBrowser(browserId,colId,itemId);
    };

}]);
