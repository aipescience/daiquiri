var app = angular.module('query',[]);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('accountService', ['$http','$timeout','$q',function($http,$timeout,$q) {
    var account = {
        active: {
            form: false,
            job: false
        },
        database: {},
        job: {},
        jobs: []
    };

    return {
        account: account,
        fetchAccount: function() {
            $http.get('query/account/').success(function(response) {
                account.jobs = response.jobs;
                account.database = response.database;
            });
        },
        fetchJob: function(id) {
            var deferred = $q.defer();

            $http.get('query/account/show-job/id/' + id).success(function(response) {
                account.job = response.job;
                deferred.resolve();
            });

            return deferred.promise;
        }
    };
}]);

app.factory('formService', ['$http','accountService',function($http,accountService) {
    var values = {};
    var errors = {};

    return {
        values: values,
        errors: errors,
        submitQuery: function(formName) {
            var data = {};
            data[formName + '_csrf'] = $('#' + formName + '_csrf').attr('value');

            // merge with form values
            angular.extend(data,values[formName]);

            $http.post('query/form/?form=' + formName,$.param(data)).success(function(response) {
                if (response['status'] == 'ok') {
                    accountService.fetchAccount();
                } else if (response['status'] == 'error') {
                    errors[formName] = {};
                    angular.forEach(response['errors'], function(object, key) {
                        errors[formName][key] = object;
                    });
                } else {
                    errors[formName] = {
                        'form': ['Unknown response.']
                    };
                }
            }).error(function () {
                errors[formName] = {
                    'form': ['Could not connect to server.']
                };
            });
        }
    };
}]);

app.factory('plotService', ['$http','accountService',function($http,accountService) {
    var values = {};
    var errors = {};

    return {
        values: values,
        errors: errors,
        createPlot: function() {
            // manual validation
            // var valid = true;
            // if (angular.isUndefined(values.plot_x)) {
            //     errors.plot_x = true;
            //     valid = false;
            // }
            // if (angular.isUndefined(values.plot_y)) {
            //     errors.plot_y = true;
            //     valid = false;
            // }
            // if (!angular.isNumber(values.plot_nrows)) {
            //     errors.plot_nrows = true;
            //     valid = false;
            // }

            // if (errors !== {}) {
            //     return;
            // }

            $http.get('data/viewer/rows/',{
                'params': {
                    'db': accountService.account.job.database,
                    'table': accountService.account.job.table,
                    'cols': values.plot_x.name + ',' + values.plot_y.name,
                    'nrows': values.plot_nrows
                }
            }).success(function(response) {
                if (response.status == 'ok') {
                    var plot = {'x': [],'y': []};

                    for (var i=0; i<response.nrows; i++) {
                        plot.x.push(response.rows[i].cell[0]);
                        plot.y.push(response.rows[i].cell[1]);
                    }

                    accountService.account.job.plot = plot;

                } else {
                    console.log('Error: Unknown response.');
                }
            }).error(function () {
                console.log('Error: Could not connect to server.');
            });
        }
    };
}]);

app.factory('downloadService', ['$http','accountService',function($http,accountService) {
    var values = {};
    var errors = {};

    return {
        values: values,
        errors: errors,
        downloadTable: function() {
            var data = {};
            data = {
                'download_csrf': $('#download_csrf').attr('value'),
                'download_tablename': accountService.account.job.table
            };

            // merge with form values
            angular.extend(data,values);

            $http.post('query/download/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    accountService.account.job.download = {
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    errors[formName] = {};
                    angular.forEach(response.status, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    errors[formName] = {
                        'form': ['Unknown response.']
                    };
                }
            }).error(function () {
                errors[formName] = {
                    'form': ['Could not connect to server.']
                };
            });
        },
        regenerateTable: function() {
            var data = {};
            data = {
                'download_csrf': $('#download_csrf').attr('value'),
                'download_tablename': accountService.account.job.table,
                'download_format': accountService.account.job.download.format
            };

            $http.post('query/download/regenerate/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    accountService.account.job.download = {
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    errors[formName] = {};
                    angular.forEach(response.status, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    errors[formName] = {
                        'form': ['Unknown response.']
                    };
                }
            }).error(function () {
                errors[formName] = {
                    'form': ['Could not connect to server.']
                };
            });
        }
    };
}]);

app.controller('sidebarController',['$scope','accountService',function($scope,accountService) {

    $scope.account = accountService.account;

    $scope.activateForm = function(formName) {
        accountService.account.active.form = formName;
        accountService.account.active.job = false;

        $('#form-tab-header a').tab('show');

        // $scope.activateJob(1);
        // $('#plot-tab-header a').tab('show');
    };

    $scope.activateJob = function(jobId) {

        accountService.fetchJob(jobId).then(function() {
            // codemirrorfy the query
            CodeMirror.runMode(accountService.account.job.query,"text/x-mysql",angular.element('#overview-query')[0]);
        });

        // if a form was active, switch to job overview tab
        if (accountService.account.active.form != false) {
            $('#overview-tab-header a').tab('show');
        }

        accountService.account.active.form = false;
        accountService.account.active.job = jobId;
    };

    accountService.fetchAccount();
    $scope.activateForm(accountService.account.active.form)
}]);

app.controller('tabsController',['$scope','accountService',function($scope,accountService) {
    $scope.account = accountService.account;
}]);

app.controller('formController',['$scope','accountService','formService',function($scope,accountService,formService) {

    $scope.values = formService.values;
    $scope.errors = formService.errors;

    $scope.submitQuery = function(formName) {
        formService.submitQuery(formName);
    }

}]);

app.controller('plotController',['$scope','plotService',function($scope,plotService) {

    $scope.values = plotService.values;
    $scope.errors = plotService.errors;
    $scope.values.plot_nrows = 100;
    $scope.values.plot_y = 2;

    $scope.createPlot = function(isValid) {
        if (isValid) {
            plotService.createPlot();
        }
    }

}]);

app.controller('downloadController',['$scope','downloadService',function($scope,downloadService) {

    $scope.values = downloadService.values;
    $scope.errors = downloadService.errors;

    $scope.downloadTable = function() {
        downloadService.downloadTable();
    }

    $scope.regenerateTable = function() {
        downloadService.regenerateTable();
    }

}]);