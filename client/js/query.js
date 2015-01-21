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

var app = angular.module('query',['table','browser','codemirror']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('QueryService', ['$http','$timeout','$q',function($http,$timeout,$q) {
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

app.factory('SubmitService', ['$http','QueryService','BrowserService',function($http,QueryService,BrowserService) {
    var values = {};
    var errors = {};

    return {
        values: values,
        errors: errors,
        submitQuery: function(formName) {
            var data = {};
            data[formName + '_csrf'] = $('#' + formName + '_csrf').attr('value');

            // merge with form values of THIS form
            angular.forEach(values, function (value, key) {
                if (key.indexOf(formName + '_') === 0) {
                    data[key] = value;
                }
            })

            // reset errors for all forms
            for (var error in errors) delete errors[error];

            $http.post('query/form/?form=' + formName,$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    QueryService.fetchAccount();
                } else if (response.status == 'error') {
                    angular.forEach(response.errors, function(error, key) {
                        errors[key] = error;
                    });
                } else {
                    errors = {
                        'form': ['Unknown response.']
                    };
                }
            }).error(function () {
                errors = {
                    'form': ['Could not connect to server.']
                };
            });
        }
    };
}]);

app.factory('BarService', ['$http','BrowserService',function($http,BrowserService) {
    BrowserService.browser.databases = {
        'url': '/query/account/databases/',
        'colnames': ['databases','tables','columns']
    };
    BrowserService.initBrowser('databases');

    BrowserService.browser.keywords = {
        'url': '/query/account/keywords/',
        'colnames': ['keywords']
    };
    BrowserService.initBrowser('keywords');

    BrowserService.browser.nativeFunctions = {
        'url': '/query/account/native-functions/',
        'colnames': ['native_functions']
    };
    BrowserService.initBrowser('nativeFunctions');

    BrowserService.browser.customFunctions = {
        'url': '/query/account/custom-functions/',
        'colnames': ['custom_functions']
    };
    BrowserService.initBrowser('customFunctions');

    BrowserService.browser.examples = {
        'url': '/query/account/examples/',
        'colnames': ['examples']
    };
    BrowserService.initBrowser('examples');

    return {
        databases: BrowserService.browser.databases,
        keywords: BrowserService.browser.keywords,
        nativeFunctions: BrowserService.browser.nativeFunctions,
        customFunctions: BrowserService.browser.customFunctions,
        examples: BrowserService.browser.examples
    };
}]);

app.factory('PlotService', ['$http','QueryService',function($http,QueryService) {
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
                    'db': QueryService.account.job.database,
                    'table': QueryService.account.job.table,
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

                    QueryService.account.job.plot = plot;

                } else {
                    console.log('Error: Unknown response.');
                }
            }).error(function () {
                console.log('Error: Could not connect to server.');
            });
        }
    };
}]);

app.factory('DownloadService', ['$http','QueryService',function($http,QueryService) {
    var values = {};
    var errors = {};

    return {
        values: values,
        errors: errors,
        downloadTable: function() {
            var data = {};
            data = {
                'download_csrf': $('#download_csrf').attr('value'),
                'download_tablename': QueryService.account.job.table
            };

            // merge with form values
            angular.extend(data,values);

            $http.post('query/download/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    QueryService.account.job.download = {
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
                'download_tablename': QueryService.account.job.table,
                'download_format': QueryService.account.job.download.format
            };

            $http.post('query/download/regenerate/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    QueryService.account.job.download = {
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

app.controller('QueryController',['$scope','$timeout','QueryService',function($scope,$timeout,QueryService) {

    $scope.account = QueryService.account;

    $scope.activateForm = function(formName) {
        QueryService.account.active.form = formName;
        QueryService.account.active.job = false;

        $('#submit-tab-header a').tab('show');
    };

    $scope.activateJob = function(jobId) {

        QueryService.fetchJob(jobId).then(function() {
            // codemirrorfy the query
            CodeMirror.runMode(QueryService.account.job.query,"text/x-mysql",angular.element('#overview-query')[0]);
        });

        // if a form was active, switch to job overview tab
        if (QueryService.account.active.form != false) {
            $('#overview-tab-header a').tab('show');
        }

        QueryService.account.active.form = false;
        QueryService.account.active.job = jobId;
    };

    QueryService.fetchAccount();

}]);

app.controller('SubmitController',['$scope','QueryService','SubmitService','CodemirrorService',function($scope,QueryService,SubmitService,CodemirrorService) {

    /* form submission */

    $scope.values = SubmitService.values;
    $scope.errors = SubmitService.errors;

    $scope.submitQuery = function(formName,event) {
        console.log();
        if (formName == 'sql') {
            CodemirrorService.save();
            $scope.values[angular.element('.codemirror').attr('id')] = angular.element('.codemirror').val();
        }
        SubmitService.submitQuery(formName);
        event.preventDefault()
    };
}]);

app.controller('BarController',['$scope','BarService','CodemirrorService',function($scope,BarService,CodemirrorService) {

    $scope.databases = BarService.databases;
    $scope.keywords = BarService.keywords;
    $scope.nativeFunctions = BarService.nativeFunctions;
    $scope.customFunctions = BarService.customFunctions;
    $scope.examples = BarService.examples;

    $scope.visible = false;

    $scope.toogleDatabases = function() {
        if ($scope.visible === 'databases') {
            $scope.visible = false;
        } else {
            $scope.visible = 'databases';
        }
    };
    $scope.toogleFunctions = function() {
        if ($scope.visible === 'functions') {
            $scope.visible = false;
        } else {
            $scope.visible = 'functions';
        }
    };
    $scope.toogleExamples = function() {
        if ($scope.visible === 'examples') {
            $scope.visible = false;
        } else {
            $scope.visible = 'examples';
        }
    };

    $scope.$on('browserItemDblClicked', function(event,browsername,value) {
        if (browsername == 'examples') {
            // empty the query input textarea
            CodemirrorService.clear();
        }

        // insert the sting into the codemirror textarea
        CodemirrorService.insert(value + ' ');
    });
}]);

app.controller('ResultsController',['$scope','QueryService','TableService',function($scope,QueryService,TableService) {

    $scope.$watch(function() {
        return QueryService.account.job;
    }, function (job) {
        if (!angular.isUndefined(job.cols)) {
            TableService.url.cols = '/data/viewer/cols?db=' + job.database + '&table=' + job.table;
            TableService.url.rows = '/data/viewer/rows?db=' + job.database + '&table=' + job.table;
            TableService.init();
        }
    });

}]);

app.controller('PlotController',['$scope','PlotService',function($scope,PlotService) {

    $scope.values = PlotService.values;
    $scope.errors = PlotService.errors;
    $scope.values.plot_nrows = 100;
    $scope.values.plot_y = 2;

    $scope.createPlot = function(isValid) {
        if (isValid) {
            PlotService.createPlot();
        }
    };

}]);

app.controller('DownloadController',['$scope','DownloadService',function($scope,DownloadService) {

    $scope.values = DownloadService.values;
    $scope.errors = DownloadService.errors;

    $scope.downloadTable = function() {
        DownloadService.downloadTable();
    };

    $scope.regenerateTable = function() {
        DownloadService.regenerateTable();
    };

}]);