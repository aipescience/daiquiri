var app = angular.module('query',[]);

app.config(['$httpProvider', function($httpProvider) {
    //$httpProvider.defaults.xsrfCookieName = 'csrftoken';
    //$httpProvider.defaults.xsrfHeaderName = 'X-CSRFToken';
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
}]);

app.factory('jobService', ['$http','$timeout',function($http,$timeout) {
    var data = {
        job: {},
        jobs: []
    };

    return {
        data: data,
        fetchJobs: function() {
            $http.get('query/account/list-jobs/').success(function(response) {
                data.jobs = response.jobs;
            });
        },
        fetchJob: function(id) {
            $http.get('query/account/show-job/id/' + id).success(function(response) {
                data.job = response.job;
            });
        }
    };
}]);

app.controller('jobController',['$scope','$timeout','jobService',function($scope,$timeout,jobService) {
    
    $scope.jobData = jobService.data;

    $scope.loadJob = function (jobId) {
        jobService.fetchJob(jobId);
    };

    jobService.fetchJobs();

}]);