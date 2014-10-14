var daiquiriTable = angular.module('table', []);

daiquiriTable.directive('daiquiriTable', function() {
    return {
        templateUrl: '/daiquiri/html/table.html'
    };
});

daiquiriTable.factory('tableService', ['$http',function($http) {
    
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

    function first() {
        if (params.page != 1) {
            params.page = 1;
            fetchRows();
        }
    };

    function prev() {
        if (params.page > 1) {
            params.page -= 1;
            fetchRows();
        }
    };

    function next() {
        if (params.page < meta.pages) {
            params.page += 1;
            fetchRows();
        }
    };

    function last() {
        if (params.page != meta.pages) {
            params.page = meta.pages;
            fetchRows();
        }
    };

    function reset() {
        params.page = 1;
        params.sort = null;
        params.search = null;
        fetchRows();
    };

    function search(searchString) {
        params.page = 1;
        params.sort = null;
        params.search = searchString;
        fetchRows();
    };

    function rows(nrows) {
        params.nrows = nrows;
        reset();
    };

    function fetchCols() {
        $http.get(url.cols,{'params': params})
            .success(function(response) {
                if (response.status == 'ok') {
                    data.cols = response.cols;
                } else {
                    console.log('Error');
                }
            })
            .error(function(response) {
                console.log('Error');
            });
    };

    function fetchRows() {
        $http.get(url.rows,{'params': params})
            .success(function(response) {
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
            .error(function(response) {
                console.log('Error');
            });
    };

    function init() {
        // fetch cols if they where not loaded before
        if (data.cols.length === 0) {
            fetchCols();
        }

        fetchRows();
    };

    return {
        url: url,
        data: data,
        meta: meta,
        first: first,
        prev: prev,   
        next: next,
        last: last,
        reset: reset,
        search: search,
        rows: rows,
        fetchCols: fetchCols,
        fetchRows: fetchRows,
        init: init
    };
}]);

daiquiriTable.controller('tableController', ['$scope','tableService',function($scope,tableService) {

    $scope.tableData = tableService.data;
    $scope.tableMeta = tableService.meta;

    $scope.nrows = 10;
    $scope.options = [
        {'name': 'Show 10 rows', 'value': 10},
        {'name': 'Show 20 rows', 'value': 20},
        {'name': 'Show 100 rows', 'value': 100}
    ];

    $scope.first = function() {
        tableService.first();
    };

    $scope.prev = function() {
        tableService.prev();
    };

    $scope.next = function() {
        tableService.next();
    };

    $scope.last = function() {
        tableService.last();
    };

    $scope.reset = function() {
        tableService.reset();
    };

    $scope.search = function() {
        tableService.search($scope.searchString);
    };

    $scope.rows = function() {
        tableService.rows($scope.nrows);
    };

}]);
