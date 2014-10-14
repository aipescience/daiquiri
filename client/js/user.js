var app = angular.module('user',['table']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.controller('UserController', ['$scope','tableService',function($scope,tableService) {
    
    tableService.data.colsUrl = '/auth/user/cols';
    tableService.data.rowsUrl = '/auth/user/rows';

    tableService.fetchCols();
    tableService.fetchRows();

}]);
