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

var app = angular.module('query',['table','modal','browser','plot','codemirror','samp','ngCookies']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);


/* filter */

app.filter('newlines', function () {
    return function(text) {
        return text.replace(/\n/g, '<br/>');
    }
})

/* directives */

app.directive('daiquiriQueryQueuesGroup', ['$timeout','SubmitService', function($timeout,SubmitService) {
    return {
        restrict: 'C',
        transclude: true,
        scope: { id: '=' },
        template: '<div ng-transclude ng-hide="buttons"></div><div class="btn-group" data-toggle="buttons-radio" ng-show="buttons"><button type="button" class="btn" ng-repeat="button in buttons" ng-class="{\'active\': button.selected }" ng-click="changeQueue(button.value)"><div rel="tooltip" data-placement="bottom" data-original-title="{{button.tooltip}}">{{button.label}} queue</div></button></div>',
        link: {
            post: function(scope, element, attrs) {
                var id = element.attr('id');
                var buttons = [];

                var select = angular.element('select',element)
                var model = select.attr('ng-model').replace('values.','');
                var option = angular.element('option',select);

                if (option.length <= 3) {
                    angular.element('option',element).each(function(i, option) {
                        var e = angular.element(option);
                        buttons.push({
                            value: e.attr('value'),
                            label: e.text(),
                            selected: !angular.isUndefined(e.attr('selected')),
                            tooltip: select.attr('data-original-title-' + (i+1))
                        });
                    })

                    scope.buttons = buttons;
                    scope.changeQueue = function(value) {
                        SubmitService.values[model] = value;
                    };
                }
            }
        }
    };
}])

/* services */

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

    var base = angular.element('base').attr('href');

    function init() {
        startPolling();
        activateForm();
        $('[rel=tooltip]').tooltip();
    }

    function poll() {
        fetchAccount();
        if (options.polling.enabled == true) {
            $timeout(poll, options.polling.timeout);
        }
    }

    function startPolling() {
        poll();
    }

    function fetchAccount() {
        $http.get(base + '/query/account/').success(function(response) {
            account.jobs = response.jobs;
            account.database = response.database;

            // activate the current job again, if its status has changed
            if (account.active.job != false && account.job.status != 'success') {
                // get the index of the job in the jobs array (by magic)
                var id = account.job.id;
                // get the index of the job in the jobs array (by magic)
                var i = account.jobs.indexOf(filterFilter(account.jobs,{'id': id})[0]);
                if (account.jobs[i].status != account.job.status) {
                    account.job.status = activateJob(id);
                }
            }
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
        $http.get(base + '/query/account/show-job/id/' + id)
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
                if (account.job.status == 'success') {
                    PlotService.init(account);
                }
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

        $http.post(base + '/query/account/rename-job/id/' + account.job.id,$.param(data))
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
        $http.post(base + '/query/account/kill-job/id/' + account.job.id,$.param({'csrf': $cookies['XSRF-TOKEN']}))
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
        $http.post(base + '/query/account/remove-job/id/' + account.job.id,$.param({'csrf': $cookies['XSRF-TOKEN']}))
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
        init: init,
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

app.factory('SubmitService', ['$http','$timeout','$cookies','QueryService','CodemirrorService',function($http,$timeout,$cookies,QueryService,CodemirrorService) {
    var values = {};
    var errors = {};

    var base = angular.element('base').attr('href');

    function init() {
        $timeout(function() {
            // refresh codemirror
            CodemirrorService.refresh('sql_query');

            // init queues field
            angular.element('.daiquiri-query-queues').each(function(key,node) {
                // get default value for queue
                var id = angular.element(node).attr('id');
                var value = angular.element('[selected="selected"]',node).attr('value');
                values[id] = value;
            });
        });
    }

    function submitted(response) {
        if (response.job.status == 'success') {
            QueryService.activateJob(response.job.id);
        } else {
            QueryService.showDialog('submitted');
            QueryService.dialog.values.table = response.job.table;
        }
        QueryService.fetchAccount();
    }

    function plan(response) {
        // get the plan from the server
        $http.get(response.redirect).success(function(res) {
            // show the plan dialog
            QueryService.showDialog('plan');

            // codemirrorfy when the plan is editable
            if (angular.element('#plan_query.codemirror').length !== 0) {
                CodemirrorService.clear('plan_query');
                $timeout(function() {
                    CodemirrorService.insert('plan_query', res.query);
                    CodemirrorService.refresh('plan_query');
                }, 100);
            } else {
                QueryService.dialog.values.plan = res.query;
            }
        }).error(function () {
            errors = {'form': ['Could not connect to server.']};
        });
    }

    function submitQuery(formName) {
        var data = {};
        data[formName + '_csrf'] = $('#' + formName + '_csrf').attr('value');

        if (formName == 'sql') {
            CodemirrorService.save('sql_query');
            values[angular.element('.codemirror').attr('id')] = angular.element('.codemirror').val();
        }

        // merge with form values of THIS form
        angular.forEach(values, function (value, key) {
            if (key.indexOf(formName + '_') === 0) {
                data[key] = value;
            }
        })

        // reset errors for all forms
        for (var error in errors) delete errors[error];

        $http.post(base + '/query/form/?form=' + formName,$.param(data)).success(function(response) {
            if (response.status == 'ok') {
                submitted(response);
            } else if (response.status == 'plan') {
                plan(response);
            } else if (response.status == 'error') {
                console.log(response);
                angular.forEach(response.errors, function(error, key) {
                    errors[key] = error;
                });
            } else {
                console.log(response);
                errors = {'form': ['Unknown response.']};
            }
        }).error(function () {
            errors = {'form': ['Could not connect to server.']};
        });
    }

    function submitPlan() {
        if (angular.element('#plan_query.codemirror').length !== 0) {
            CodemirrorService.save('plan_query');
        }

        $http.post(base + '/query/form/plan',$.param({
            'plan_csrf': $cookies['XSRF-TOKEN'],
            'plan_query': angular.element('#plan_query').val()
        })).success(function(response) {
            if (response.status == 'ok') {
                submitted(response);
            } else if (response.status == 'error') {
                console.log(response);
                angular.forEach(response.errors, function(error, key) {
                    errors[key] = error;
                });
            } else {
                console.log(response);
                errors = {'form': ['Unknown response.']};
            }
        }).error(function () {
            errors = {'form': ['Could not connect to server.']};
        });
    }

    function pasteQuery() {
        QueryService.activateForm('sql');

        var query = angular.element('#overview-query')[0].innerText;
        $timeout(function() {
            CodemirrorService.clear('sql_query');
            CodemirrorService.insert('sql_query',query);
            CodemirrorService.refresh('sql_query');
        });
    }

    function clearInput() {
        CodemirrorService.clear('sql_query');
    }

    function insertIntoQuery(browsername,value) {
        if (browsername == 'examples') {
            // empty the query input textarea
            CodemirrorService.clear('sql_query');
        }

        // insert the sting into the codemirror textarea
        CodemirrorService.insert('sql_query', value + ' ');
    }

    return {
        values: values,
        errors: errors,
        init: init,
        submitQuery: submitQuery,
        submitPlan: submitPlan,
        pasteQuery: pasteQuery,
        clearInput: clearInput,
        insertIntoQuery: insertIntoQuery
    };
}]);

app.factory('BarService', ['BrowserService',function(BrowserService) {
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

app.factory('DownloadService', ['$http','QueryService',function($http,QueryService) {
    var values = {};
    var errors = {};

    var base = angular.element('base').attr('href');

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

            $http.post(base + '/query/download/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    QueryService.account.job.download = {
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    for (var error in errors) delete errors[error];
                    console.log(response);
                    angular.forEach(response.errors, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    console.log(response);
                    errors[formName] = {'form': ['Unknown response.']};
                }
            }).error(function () {
                errors[formName] = {'form': ['Could not connect to server.']};
            });
        },
        regenerateTable: function() {
            var data = {};
            data = {
                'download_csrf': $('#download_csrf').attr('value'),
                'download_tablename': QueryService.account.job.table,
                'download_format': QueryService.account.job.download.format
            };

            $http.post(base + '/query/download/regenerate/',$.param(data)).success(function(response) {
                if (response.status == 'ok') {
                    QueryService.account.job.download = {
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    errors[formName] = {};
                    console.log(response);
                    angular.forEach(response.status, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    console.log(response);
                    errors[formName] = {'form': ['Unknown response.']};
                }
            }).error(function () {
                errors[formName] = {'form': ['Could not connect to server.']};
            });
        }
    };
}]);

/* controllers */

app.controller('QueryController',['$scope','$timeout','QueryService','SubmitService',function($scope,$timeout,QueryService,SubmitService) {

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
        SubmitService.pasteQuery();
    };

    $scope.clearInput = function() {
        SubmitService.clearInput();
    }

    // init query interface
    $timeout(function() {
        for (var option in $scope.options) QueryService.options[option] = $scope.options[option];

        QueryService.init();
        SubmitService.init();
    });

    $scope.$on('browserItemDblClicked', function(event,browsername,value) {
        SubmitService.insertIntoQuery(browsername,value);
    });

}]);

app.controller('SubmitController',['$scope','SubmitService',function($scope,SubmitService) {

    $scope.values = SubmitService.values;
    $scope.errors = SubmitService.errors;

    $scope.submitQuery = function(formName,event) {
        SubmitService.submitQuery(formName);
        event.preventDefault()
    };

    $scope.submitPlan = function() {
        SubmitService.submitPlan();
    }
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
            var base = angular.element('base').attr('href');

            TableService.url.cols = base + '/data/viewer/cols?db=' + job.database + '&table=' + job.table;
            TableService.url.rows = base + '/data/viewer/rows?db=' + job.database + '&table=' + job.table;
            TableService.init();
        }
    });
}]);

app.controller('SampController',['$scope','SampService','QueryService',function($scope,SampService,QueryService) {

    $scope.clients = SampService.clients;
    $scope.errors = SampService.errors;

    $scope.$watch(function() {
        return SampService.isConnected();
    }, function (connected) {
        $scope.connected = connected;
    });

    $scope.$watch(function() {
        return SampService.getInfo();
    }, function (info) {
        if (info !== false) {
            QueryService.showDialog('samp');
            QueryService.dialog.samp = info.text;
        }
    });

    $scope.register = function() {
        SampService.register();
    };

    $scope.unregister  = function() {
        SampService.unregister();
    };

    $scope.ping = function(id) {
        SampService.ping(id);
    };

    $scope.send = function(id) {
        SampService.send(id, QueryService.account.job.table, QueryService.account.job.username);
    };
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