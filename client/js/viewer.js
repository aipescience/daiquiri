var app = angular.module('viewer',['table']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.controller('ViewerController', ['$scope','tableService',function($scope,tableService) {
    
    tableService.url.cols = '/data/viewer/cols?db=daiquiri_user_admin&table=100';
    tableService.url.rows = '/data/viewer/rows?db=daiquiri_user_admin&table=100';

    tableService.init();
}]);
