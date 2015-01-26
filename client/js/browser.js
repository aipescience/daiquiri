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

angular.module('browser',[])

.directive('daiquiriBrowser', ['BrowserService',function(BrowserService) {
    return {
        templateUrl: angular.element('base').attr('href') + '/daiquiri/html/browser.html',
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
                'value': item.value,
                'order': item.order
            })
        });
        return items;
    }

    function updateBrowser(name, colname, i) {
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
            if (!angular.isUndefined(colname2) && browser[name].data[colname0][i][colname1].length !== 0) {
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
        // with of one column of the browser
        var width;
        if (browser[name].colnames.length == 1) {
            width = '100%';
        } else {
            width = Math.floor(220 + (1 - 1 / browser[name].colnames.length) * 20 - 1);
        }

        var selected0;
        if (angular.isUndefined(browser[name].cols) || angular.isUndefined(browser[name].cols[0])) {
            selected0 = 0;
        } else {
            selected0 = browser[name].cols[0].selected;
        }

        var selected1;
        if (angular.isUndefined(browser[name].cols) || angular.isUndefined(browser[name].cols[1])) {
            selected1 = 0;
        } else {
            selected1 = browser[name].cols[1].selected;
        }

        var active = browser[name].active;

        var colname0 = browser[name].colnames[0];
        var colname1 = browser[name].colnames[1];
        var colname2 = browser[name].colnames[2];

        $http.get(browser[name].url).success(function(response) {
            if (response.status == 'ok') {
                browser[name].data = response;

                browser[name].name = name;
                browser[name].cols = [];

                for (var i=0; i<browser[name].colnames.length; i++) {
                    var colname = browser[name].colnames[i];
                    if (!angular.isUndefined(colname)) {
                        browser[name].cols.push({
                            'id': i,
                            'name': colname.replace('_',' '),
                            'height': height,
                            'width': width,
                            'items': []
                        });
                    }
                }

                // init FIRST column
                if (!angular.isUndefined(colname0)) {
                    browser[name].cols[0].items = getItems(browser[name].data[colname0]);

                    // check if something in the FIRST column is active
                    if (!angular.isUndefined(active) && active.colname === colname0) {
                        for (var i=0; i<browser[name].cols[0].items.length;i++) {
                            if (browser[name].cols[0].items[i].id == active.id) {
                                selected0 = i;
                                break;
                            }
                        }
                    }

                    // init SECOND column
                    var data0 = browser[name].data[colname0][selected0];
                    if (!angular.isUndefined(colname1) && !angular.isUndefined(data0)) {
                        browser[name].cols[1].items = getItems(data0[colname1]);
                        browser[name].cols[0].selected = selected0;

                        // check if something in the SECOND column is active
                        if (!angular.isUndefined(active) && active.colname === colname1) {
                            for (var i=0; i<browser[name].cols[1].items.length;i++) {
                                if (browser[name].cols[1].items[i].id == active.id) {
                                    selected1 = i;
                                    break;
                                }
                            }
                        }

                        // init THIRD column
                        var data1 = data0[colname1][selected1];
                        if (!angular.isUndefined(colname2) && !angular.isUndefined(data1)) {
                            browser[name].cols[2].items = getItems(data1[colname2]);
                            browser[name].cols[1].selected = selected1;
                        }
                    }
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

    $scope.browserItemClicked = function(browsername,item,colname,i) {
        BrowserService.updateBrowser(browsername,colname,i);

        var active = {
            'browsername': browsername,
            'colname': colname,
            'id': item.id
        };
        angular.forEach(BrowserService.browser, function(browser, key) {
            browser.active = active;
        });

        $scope.$emit('browserItemClicked',browsername,item.value);
    };

    $scope.browserItemDblClicked = function(browsername,item) {
        $scope.$emit('browserItemDblClicked',browsername,item.value);
    };

}]);
