var app = angular.module('user',['table']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.controller('UserController', ['$scope','TableService',function($scope,TableService) {
    
    TableService.url.cols = '/auth/user/cols';
    TableService.url.rows = '/auth/user/rows';

    TableService.init();

}]);
