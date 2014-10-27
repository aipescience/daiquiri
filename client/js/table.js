var daiquiriTable = angular.module('table', ['ngSanitize']);

daiquiriTable.directive('daiquiriTable', ['$compile','TableService',function($compile,TableService) {
    return {
        templateUrl: '/daiquiri/html/table.html',
        link: function (scope, element, attrs) {
            // watch the cols for a change, and perform callback
            if (angular.isFunction(TableService.callback.cols)) {
                scope.$watch(function () {
                    return TableService.data.cols;
                }, function(newValue, oldValue) {
                    scope.$evalAsync(function($scope) {
                        TableService.callback.cols($scope);
                    });
                }, true);
            }

            // watch the rows for a change, and perform callback
            if (angular.isFunction(TableService.callback.rows)) {
                scope.$watch(function () {
                    return TableService.data.rows;
                }, function(newValue, oldValue) {
                    scope.$evalAsync(function($scope) {
                        TableService.callback.rows($scope);
                    });
                }, true);
            }
        }
    };
}]);

daiquiriTable.factory('TableService', ['$http','$q','$timeout',function($http,$q,$timeout) {
    
    var url = {
        cols: null,
        rows: null
    };

    var data = {
        cols: [],
        rows: []
    };

    var meta = {
        nrows: null,
        page: null,
        pages: null,
        total: null
    };

    var params = {
        'nrows': 10,
        'page': 1,
        'sort': null,
        'search': null
    };

    var callback = {
        'rows': null,
        'cols': null
    };

    function first() {
        if (params.page != 1) {
            params.page = 1;
            fetchRows();
        }
    }

    function prev() {
        if (params.page > 1) {
            params.page -= 1;
            fetchRows();
        }
    }

    function next() {
        if (params.page < meta.pages) {
            params.page += 1;
            fetchRows();
        }
    }

    function last() {
        if (params.page != meta.pages) {
            params.page = meta.pages;
            fetchRows();
        }
    }

    function reset() {
        params.page = 1;
        params.sort = null;
        params.search = null;
        fetchRows();
    }

    function search(searchString) {
        params.page = 1;
        params.sort = null;
        params.search = searchString;
        fetchRows();
    }

    function changeNRows(nrows) {
        params.nrows = nrows;
        reset();
    }

    function fetchCols() {
        $http.get(url.cols,{'params': params}).success(function(response) {
            if (response.status == 'ok') {
                data.cols = response.cols;
            } else {
                console.log('Error');
            }
        })
    }

    function fetchRows() {
        $http.get(url.rows,{'params': params}).success(function(response) {
            if (response.status == 'ok') {
                data.rows = response.rows;
                meta.nrows = response.nrows;
                meta.page = response.page;
                meta.pages = response.pages;
                meta.total = response.total;
            } else {
                console.log('Error');
            }
        })
    }

    function init() {
        // fetch cols if they where not loaded before
        if (data.cols.length === 0) fetchCols();

        // fetch cols
        fetchRows();
    }

    return {
        url: url,
        data: data,
        meta: meta,
        callback: callback,
        first: first,
        prev: prev,
        next: next,
        last: last,
        reset: reset,
        search: search,
        changeNRows: changeNRows,
        fetchCols: fetchCols,
        fetchRows: fetchRows,
        init: init
    };
}]);

daiquiriTable.controller('TableController', ['$scope','TableService',function($scope,TableService) {

    $scope.tableData = TableService.data;
    $scope.tableMeta = TableService.meta;

    $scope.nrows = 10;
    $scope.options = [
        {'name': 'Show 10 rows', 'value': 10},
        {'name': 'Show 20 rows', 'value': 20},
        {'name': 'Show 100 rows', 'value': 100}
    ];

    $scope.first = function() {
        TableService.first();
    };

    $scope.prev = function() {
        TableService.prev();
    };

    $scope.next = function() {
        TableService.next();
    };

    $scope.last = function() {
        TableService.last();
    };

    $scope.reset = function() {
        TableService.reset();
    };

    $scope.search = function() {
        TableService.search($scope.searchString);
    };

    $scope.changeNRows = function() {
        TableService.changeNRows($scope.nrows);
    };
}]);
