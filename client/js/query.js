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

var app = angular.module('query',['table','modal','browser','images','plot','codemirror','samp','ngCookies']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

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
                            selected: angular.isDefined(e.attr('selected')),
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

app.factory('PollingService', ['$timeout','QueryService','DownloadService',function($timeout,QueryService,DownloadService) {
    // query options, will be set inside the template via ng-init
    var options = {};

    function init(opt) {
        options = opt;
        poll();
    }

    function poll() {
        QueryService.fetchAccount();

        if (angular.isDefined(QueryService.account.job.download) && QueryService.account.job.download.status == 'pending') {
            DownloadService.downloadTable(QueryService.account.job.download.format);
        }

        if (options.polling.enabled == true) {
            $timeout(poll, options.polling.timeout);
        }
    }

    return {
        init: init,
        poll: poll
    };
}]);

app.factory('QueryService', ['$http','$timeout','$window','filterFilter','ModalService','PlotService','BrowserService',function($http,$timeout,$window,filterFilter,ModalService,PlotService,BrowserService) {
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

    function init(opt) {
        options = opt;
        activateForm();
        $('[rel=tooltip]').tooltip();
    }

    function fetchAccount() {
        $http.get(base + '/query/account/')
            .success(function(response) {
                // update database information (top left)
                account.database = response.database;

                // update job list and database browser if something has changed
                if (!angular.equals(account.jobs,response.jobs)) {
                    account.jobs = response.jobs;
                    BrowserService.initBrowser('databases');
                }

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
            })
            .error(function(response,status) {
                if (status === 403) {
                    $window.location.reload();
                } else {
                    console.log(response);
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

                // codemirrorfy the query and the plan
                CodeMirror.runMode(account.job.query,"text/x-mysql",angular.element('#overview-query')[0]);
                if (angular.isDefined(account.job.plan)) {
                    CodeMirror.runMode(account.job.plan,"text/x-mysql",angular.element('#overview-plan')[0]);
                }
                if (angular.isDefined(account.job.actualQuery)) {
                    CodeMirror.runMode(account.job.actualQuery,"text/x-mysql",angular.element('#overview-actualQuery')[0]);
                }

                // if a form was active, the image tab was active or the job was not a success, switch to job overview tab
                if (account.active.form != false || $('#images-tab-header').hasClass('active') || account.job.status != 'success') {
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
                if (status === 403) {
                    $window.location.reload();
                } else {
                    console.log(response);
                }
            });
    }

    function renameJob () {
        var data = {
            'csrf': options.csrf,
            'tablename': dialog.values.tablename
        };

        $http.post(base + '/query/account/rename-job/id/' + account.job.id,$.param(data))
            .success(function(response) {
                dialogSuccess(response, function() {
                    account.job.table = data.tablename;
                    PlotService.values.table = data.tablename;
                    fetchAccount();
                });
            })
            .error(function (response,status) {
                dialogError(response,status);
            });
    }

    function killJob () {
        $http.post(base + '/query/account/kill-job/id/' + account.job.id,$.param({'csrf': options.csrf}))
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
        $http.post(base + '/query/account/remove-job/id/' + account.job.id,$.param({'csrf': options.csrf}))
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
            ModalService.close();
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
        ModalService.open();
    }

    function hideDialog() {
        dialog.enabled = false;
        ModalService.close();
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

app.factory('SubmitService', ['$http','$timeout','QueryService','CodemirrorService',function($http,$timeout,QueryService,CodemirrorService) {
    // query options, will be set inside the template via ng-init
    var options = {};

    var values = {};
    var errors = {};

    var base = angular.element('base').attr('href');

    function init(opt) {
        options = opt;

        angular.element('input[type="text"]').each(function(key, node) {
            var element = angular.element(node);

            var id = element.attr('id');
            var value = element.attr('value');

            if (angular.isDefined(value)) {
                values[id] = value;
            }
        });

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
            'plan_csrf': options.csrf,
            'plan_query': angular.element('#plan_query').val()
        })).success(function(response) {
            if (response.status == 'ok') {
                submitted(response);
            } else if (response.status == 'error') {
                console.log(response);
                angular.forEach(response.errors, function(error, key) {
                    errors['plan_query'] = error;
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

        $timeout(function() {
            CodemirrorService.clear('sql_query');
            CodemirrorService.insert('sql_query',QueryService.account.job.query);
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
    // query options, will be set inside the template via ng-init
    var options = {};

    var errors = {};

    var base = angular.element('base').attr('href');

    return {
        errors: errors,
        init: function(opt) {
            options = opt;
        },
        downloadTable: function(format) {
            var data = {};
            data = {
                'download_csrf': options.csrf,
                'download_tablename': QueryService.account.job.table,
                'download_format': format
            };

            $http.post(base + '/query/download/',$.param(data)).success(function(response) {
                for (var error in errors) delete errors[error];
                QueryService.account.job.download = false;

                if (response.status == 'ok') {
                    QueryService.account.job.download = {
                        'status': 'ok',
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'pending') {
                    QueryService.account.job.download = {
                        'status': 'pending',
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    console.log(response);
                    angular.forEach(response.errors, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    console.log(response);
                    errors['form'] = ['Unknown response from server, please contact support.'];

                }
            }).error(function () {
                errors['form'] = ['Error with connection to server, please contact support.'];
            });
        },
        regenerateTable: function(format) {
            var data = {};
            data = {
                'download_csrf': options.csrf,
                'download_tablename': QueryService.account.job.table,
                'download_format': QueryService.account.job.download.format
            };

            $http.post(base + '/query/download/regenerate/',$.param(data)).success(function(response) {
                for (var error in errors) delete errors[error];
                QueryService.account.job.download = false;

                if (response.status == 'ok') {
                    QueryService.account.job.download = {
                        'status': 'ok',
                        'link': response.link,
                        'format': response.format
                    }
                } else if (response.status == 'pending') {
                    QueryService.account.job.download = {
                        'status': 'pending',
                        'format': response.format
                    }
                } else if (response.status == 'error') {
                    console.log(response);
                    angular.forEach(response.status, function(object, key) {
                        errors[key] = object;
                    });
                } else {
                    console.log(response);
                    errors['form'] = ['Unknown response from server, please contact support.'];
                }
            }).error(function () {
                errors['form'] = ['Error with connection to server, please contact support.'];
            });
        }
    };
}]);

/* controllers */

app.controller('QueryController',['$scope','$timeout','PollingService','QueryService','SubmitService','DownloadService',function($scope,$timeout,PollingService,QueryService,SubmitService,DownloadService) {

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

    $scope.tabclick = function ($event) {
        // prevents the bootstrap tabs from changing the location
        $event.preventDefault();
    }

    // init query interface
    $timeout(function() {
        PollingService.init($scope.options);
        QueryService.init($scope.options);
        SubmitService.init($scope.options);
        DownloadService.init($scope.options);
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

    $scope.$on('browserItemDblClicked', function(event,browsername,value) {
        SubmitService.insertIntoQuery(browsername,value);
    });
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

app.controller('ResultsController',['$scope','$window','QueryService','ImagesService','DownloadService','TableService',function($scope,$window,QueryService,ImagesService,DownloadService,TableService) {

    $scope.values = {
        iCol: null,
        iRows: []
    };

    $scope.$watch(function() {
        return QueryService.account.job;
    }, function (job) {
        if (angular.isDefined(job.cols)) {
            var base = angular.element('base').attr('href');

            TableService.url.cols = base + '/data/viewer/cols?db=' + job.database + '&table=' + job.table;
            TableService.url.rows = base + '/data/viewer/rows?db=' + job.database + '&table=' + job.table;
            TableService.init();

            ImagesService.init();
        }
    });

    $scope.$on('tableReferenceClicked', function(event,iCol,iRow) {

        var col = TableService.data.cols[iCol];
        var value = TableService.data.rows[iRow].cell[iCol];

        if (col.ucd.indexOf('meta.file') != -1) {

            extension = value.match(/(?:\.([^.]+))?$/)[1];
            if (['jpg','jpeg','png','bmp'].indexOf(extension) != -1) {
                // an image file
                ImagesService.show(iCol,iRow);
                $('#images-tab-header a').tab('show');
            } else {
                // a regular file to be downloaded
                var base = angular.element('base').attr('href');
                var link = base + '/data/files/single/name/' + value;
                $('<iframe />', {
                    'style': 'visibility: hidden; height: 0; width: 0;',
                    'src': link
                }).appendTo('body');
            }
        } else if (col.ucd.indexOf('meta.fits') != -1) {
            // a fits file
            var base = angular.element('base').attr('href');
            var link = base + '/data/files/single/name/' + value;
            $('<iframe />', {
                'style': 'visibility: hidden; height: 0; width: 0;',
                'src': link
            }).appendTo('body');
        } else {
            // regular link
            $window.open(value,'_blank');
        }
    });

    $scope.$on('tableColSelected', function(event,iCol) {
        $scope.values.iCol = iCol;
    });

    $scope.$on('tableRowSelected', function(event,iRows) {
        $scope.values.iRows = iRows;
    });

    $scope.downloadCol = function(iCol) {
        var base = angular.element('base').attr('href');
        var link = base + '/data/files/multi?table=' + QueryService.account.job.table + '&column=' + QueryService.account.job.cols[iCol].name;
        $('<iframe />', {
             'style': 'visibility: hidden; height: 0; width: 0;',
             'src': link
        }).appendTo('body');
    }

    $scope.downloadRows = function(iRows) {
        var base = angular.element('base').attr('href');
        var link = base + '/data/files/row?table=' + QueryService.account.job.table + '&id=' + iRows.map(function(i) {return i+1;}).join();
        $('<iframe />', {
             'style': 'visibility: hidden; height: 0; width: 0;',
             'src': link
        }).appendTo('body');
    }

    $scope.toggleColumn = function(iCol) {
        var col = QueryService.account.job.cols[iCol];
        if (angular.isUndefined(col.hidden) || col.hidden !== true) {
            col.hidden = true;
            TableService.hideCol(iCol);
        } else {
            col.hidden = false;
            TableService.showCol(iCol);
        }
    }

}]);

app.controller('ImagesController',['$scope','ImagesService',function($scope,ImagesService) {

    $scope.values = ImagesService.values;

    $scope.first = function() {
        ImagesService.first();
    };

    $scope.prev = function() {
        ImagesService.prev();
    };

    $scope.next = function() {
        ImagesService.next();
    };

    $scope.last = function() {
        ImagesService.last();
    };
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

    $scope.errors = DownloadService.errors;

    $scope.downloadTable = function(format) {
        DownloadService.downloadTable(format);
    };

    $scope.regenerateTable = function(format) {
        DownloadService.regenerateTable(format);
    };

    $scope.downloadFile = function(link) {
        $('<iframe />', {
             'style': 'visibility: hidden; height: 0; width: 0;',
             'src': link
        }).appendTo('body');
    };

}]);