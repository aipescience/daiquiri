/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>,
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.'ngCookies'
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var app = angular.module('query',['table','modal','browser','codemirror','ngCookies']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('QueryService', ['$http','$timeout','$cookies','filterFilter','ModalService','PlotService',function($http,$timeout,$cookies,filterFilter,ModalService,PlotService) {
    // query options, will be set inside the template via ng-init
    var options = {};

    // account information, will be fetched via ajax
    var account = {
        active: {
            form: false,
            job: false
        },
        database: {},
        job: {},
        jobs: []
    };

    // dialog state, use for the different modal which will be displayed to the user
    var dialog = {
        values: {},
        errors: {},
        enabled: false
    };

    function fetchAccount() {
        $http.get('query/account/').success(function(response) {
            account.jobs = response.jobs;
            account.database = response.database;
        });
    }

    function activateForm(formName) {
        if (angular.isUndefined(formName)) {
            if (options.defaultForm == null) {
                formName = options.forms[0].key;
            } else {
                formName = options.defaultForm;
            }
        }

        account.active.form = formName;
        account.active.job = false;

        $('#submit-tab-header a').tab('show');
    }

    function activateJob(id) {
        $http.get('query/account/show-job/id/' + id)
            .success(function(response) {
                account.job = response.job;

                // codemirrorfy the query
                CodeMirror.runMode(account.job.query,"text/x-mysql",angular.element('#overview-query')[0]);

                // if a form was active, switch to job overview tab
                if (account.active.form != false) {
                    $('#overview-tab-header a').tab('show');
                }

                account.active.form = false;
                account.active.job = id;

                // init plot
                PlotService.values.db = account.job.database;
                PlotService.values.table = account.job.table;
                PlotService.values.plot_x = account.job.cols[1];
                PlotService.values.plot_y = account.job.cols[2];
                $('#plot-canvas').children().remove();
            })
            .error(function(response, status) {
                if (status === 404) {
                    fetchAccount();
                    // TODO load another job
                } else if (status === 403) {
                    // TODO show a modal to reload
                    console.log(status);
                } else {
                    // show a modal
                    console.log(status);
                }
            });
    }

    function renameJob () {
        var data = {
            'csrf': $cookies['XSRF-TOKEN'],
            'tablename': dialog.values.tablename
        };

        $http.post('query/account/rename-job/id/' + account.job.id,$.param(data))
            .success(function(response) {
                dialogSuccess(response, function() {
                    account.job.table = data.tablename;
                    fetchAccount();
                });
            })
            .error(function (response,status) {
                dialogError(response,status);
            });
    }

    function killJob () {
        $http.post('query/account/kill-job/id/' + account.job.id,$.param({'csrf': $cookies['XSRF-TOKEN']}))
            .success(function(response) {
                dialogSuccess(response, function() {
                    // get the index of the job in the jobs array (by magic)
                    var i = account.jobs.indexOf(filterFilter(account.jobs,{'id': account.job.id})[0]);

                    if (account.jobs.length == 1) {
                        // no jobs left, jump to form
                        activateForm();
                    } else if (i == account.jobs.length - 1) {
                        // this was the last job, jump to the previous job
                        activateJob(account.jobs[i-1].id)
                    } else {
                        // jump to the next job in array
                        activateJob(account.jobs[i+1].id)
                    }
                });
            })
            .error(function (response,status) {
                dialogError(response,status);
            });
    }

    function removeJob() {
        $http.post('query/account/remove-job/id/' + account.job.id,$.param({'csrf': $cookies['XSRF-TOKEN']}))
            .success(function(response) {
                dialogSuccess(response, function() {
                    // get the index of the job in the jobs array (by magic)
                    var i = account.jobs.indexOf(filterFilter(account.jobs,{'id': account.job.id})[0]);

                    if (account.jobs.length == 1) {
                        // no jobs left, jump to form
                        activateForm();
                    } else if (i == account.jobs.length - 1) {
                        // this was the last job, jump to the previous job
                        activateJob(account.jobs[i-1].id)
                    } else {
                        // jump to the next job in array
                        activateJob(account.jobs[i+1].id)
                    }
                });
            })
            .error(function (response,status) {
                dialogError(response,status);
            });
    }

    function dialogSuccess(response, callback) {
        if (response.status == 'ok') {
            if (angular.isFunction(callback)) callback(response);
            fetchAccount();
            ModalService.modal.enabled = false;
        } else if (response.status == 'error') {
            angular.forEach(response.errors, function(error, key) {
                dialog.errors[key] = error;
            });
        } else {
            dialog.errors.form = 'An error occured (' + response.status + ').';
        }
    }

    function dialogError(response,status) {
        if (status === 404) {
            dialog.errors.form = 'The selected job can not be found. Please reload the page to update the job list.';
        } else {
            dialog.errors.form = 'An error occured (' + status + '). Please reload the page.';
        }
    };

    function showDialog(key) {
        for (var value in dialog.values) delete dialog.values[value];
        for (var error in dialog.errors) delete dialog.errors[error];

        if (key === 'rename') {
            dialog.values.tablename = account.job.table;
        }
        dialog.enabled = key;
        ModalService.modal.enabled = true;
    }

    function hideDialog() {
        dialog.enabled = false;
        ModalService.modal.enabled = false;
    }

    return {
        options: options,
        account: account,
        dialog: dialog,
        fetchAccount: fetchAccount,
        activateForm: activateForm,
        activateJob: activateJob,
        renameJob: renameJob,
        killJob: killJob,
        removeJob: removeJob,
        showDialog: showDialog,
        hideDialog: hideDialog
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

app.factory('PlotService', ['$http',function($http) {
    var values = {};
    var errors = {};
    var labels = {};

    // initial values
    values.plot_nrows = 100;
    values.plot_x_scale = 'lin';
    values.plot_y_scale = 'lin';

    // function to format the tics
    function tickFormatter(val, axis) {
        if (val > 1000) {
            var exp = Math.floor(Math.log(val) / Math.LN10);
            var man = val / Math.pow(10,exp);
            return (man).toFixed(3) + "E" + exp;
        } else {
            return val.toFixed(axis.tickDecimals);
        }
    };

    return {
        values: values,
        errors: errors,
        labels: labels,
        createPlot: function() {
            for (var error in errors) delete errors[error];

            // manual validation
            var valid = true;
            if (angular.isUndefined(values.plot_x)) {
                errors.plot_x = ['Please select a column'];
                valid = false;
            }
            if (angular.isUndefined(values.plot_y)) {
                errors.plot_y = ['Please select a column'];
                valid = false;
            }

            // parse ranges
            angular.forEach(['plot_x_min','plot_x_max'], function(key) {
                if (values[key] === '') delete values[key];

                if (!angular.isUndefined(values[key])) {
                    var f = parseFloat(values[key]);
                    if (isNaN(f)) {
                        errors.plot_x_range = ['Please give a numerial value'];
                        valid = false;
                    } else {
                        values[key] = f;
                    }
                }
            });
            angular.forEach(['plot_y_min','plot_y_max'], function(key) {
                if (values[key] === '') delete values[key];

                if (!angular.isUndefined(values[key])) {
                    var f = parseFloat(values[key]);
                    if (isNaN(f)) {
                        errors.plot_y_range = ['Please give a numerial value'];
                        valid = false;
                    } else {
                        values[key] = f;
                    }
                }
            });

            // return if validation fails
            if (valid === false) return;

            // obtain the data from the server
            $http.get('data/viewer/rows/',{
                'params': {
                    'db': values.db,
                    'table': values.table,
                    'cols': values.plot_x.name + ',' + values.plot_y.name,
                    'nrows': values.plot_nrows
                }
            }).success(function(response) {
                if (response.status == 'ok') {
                    var data = [];
                    for (var i=0; i<response.nrows; i++) {
                        data.push([response.rows[i].cell[0],response.rows[i].cell[1]]);
                    }

                    // create plot
                    var options = {
                        lines: {
                            show: false
                        },
                        points: {
                            radius: 1,
                            show: true,
                            fill: true
                        },
                        shadowSize: 0,
                        xaxis: {
                            tickFormatter: tickFormatter,
                            label: 'fff'
                        },
                        yaxis: {
                            tickFormatter: tickFormatter
                        },
                        zoom: {
                            interactive: true
                        },
                        pan: {
                            interactive: true
                        }
                    };

                    if (!angular.isUndefined(values.plot_x_min)) options.xaxis.min = values.plot_x_min;
                    if (!angular.isUndefined(values.plot_x_max)) options.xaxis.max = values.plot_x_max;
                    if (!angular.isUndefined(values.plot_y_min)) options.yaxis.min = values.plot_y_min;
                    if (!angular.isUndefined(values.plot_y_max)) options.yaxis.max = values.plot_y_max;

                    if (values.plot_x_scale === 'log') options.xaxis.transform = function(v) {return Math.log(v+0.0001)};
                    if (values.plot_y_scale === 'log') options.yaxis.transform = function(v) {return Math.log(v+0.0001)};

                    $.plot('#plot-canvas', [{
                        color: "#08c",
                        data: data
                    }],options);

                    // set axes label
                    labels.x = values.plot_x.name;
                    if (values.plot_x.unit.length > 0) labels.x += ' [' + values.plot_x.unit + ']';

                    labels.y = values.plot_y.name;
                    if (values.plot_y.unit.length > 0) labels.y += ' [' + values.plot_y.unit + ']';

                } else {
                    errors.form = ['There was a problem receiving the data.']
                }
            }).error(function () {
                errors.form = ['Could not connect to server.']
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
                    for (var error in errors) delete errors[error];

                    angular.forEach(response.errors, function(object, key) {
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

app.controller('QueryController',['$scope','$timeout','QueryService','CodemirrorService','ModalService',function($scope,$timeout,QueryService,CodemirrorService,ModalService) {

    $scope.account = QueryService.account;
    $scope.dialog  = QueryService.dialog;

    $scope.activateForm = function(formName) {
        QueryService.activateForm(formName);
    };

    $scope.activateJob = function(jobId) {
        QueryService.activateJob(jobId);
    };

    $scope.showDialog = function(key) {
        QueryService.showDialog(key);
    }

    $scope.hideDialog = function() {
        QueryService.hideDialog();
    }

    $scope.renameJob = function() {
        QueryService.renameJob();
    }

    $scope.killJob = function() {
        QueryService.killJob();
    }

    $scope.removeJob = function() {
        QueryService.removeJob();
    }

    $scope.pasteQuery = function() {
        var query = angular.element('#overview-query')[0].innerText;

        $scope.activateForm('sql');
        $timeout(function() {
            CodemirrorService.clear();
            CodemirrorService.insert(query);
            CodemirrorService.refresh();
        }, 0);
    };

    $scope.clearInput = function() {
        CodemirrorService.clear();
    }

    QueryService.fetchAccount();

    $timeout(function() {
        for (var option in $scope.options) QueryService.options[option] = $scope.options[option];
        QueryService.activateForm();
    }, 0);

    $scope.$on('browserItemDblClicked', function(event,browsername,value) {
        if (browsername == 'examples') {
            // empty the query input textarea
            CodemirrorService.clear();
        }

        // insert the sting into the codemirror textarea
        CodemirrorService.insert(value + ' ');
    });

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

app.controller('BarController',['$scope','BarService',function($scope,BarService) {

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
    $scope.labels = PlotService.labels;

    $scope.createPlot = function() {
        PlotService.createPlot();
    };

}]);

app.controller('DownloadController',['$scope','DownloadService',function($scope,DownloadService) {

    $scope.values = DownloadService.values;
    $scope.errors = DownloadService.errors;

    $scope.downloadTable = function(event) {
        DownloadService.downloadTable();
        event.preventDefault();
    };

    $scope.regenerateTable = function() {
        DownloadService.regenerateTable();
    };

}]);