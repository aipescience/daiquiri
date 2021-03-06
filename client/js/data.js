/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

var app = angular.module('data',['browser','modal','ngSanitize']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('DataService', ['$http','BrowserService','ModalService',function($http,BrowserService,ModalService) {
    var view = {};
    var values = {};
    var errors = {};
    var active = {
        databaseId: null,
        tableId: null,
        url: null
    };

    var base = angular.element('base').attr('href');

    // init databases browser
    BrowserService.browser.databases = {
        'url': '/data/databases/',
        'colnames': ['databases','tables','columns']
    };
    BrowserService.initBrowser('databases');

    BrowserService.browser.functions = {
        'url': '/data/functions/',
        'colnames': ['functions']
    };
    BrowserService.initBrowser('functions');

    BrowserService.browser.functions = {
        'url': '/data/functions/',
        'colnames': ['functions']
    };
    BrowserService.initBrowser('functions');

    function fetchView(url) {
        $http.get(base + url).success(function(data) {
            view.showUrl   = url.substring(1);
            view.updateUrl = url.substring(1).replace('/show/','/update/');
            view.deleteUrl = url.substring(1).replace('/show/','/delete/');

            var model = url.match(/^\/data\/(.*?)\/show\//)[1];
            view.model = model.substring(0,1).toUpperCase() + model.substring(1,model.length-1);

            view.name = '';
            if (angular.isDefined(data.row.database)) {
                view.name += data.row.database + '.';
            }
            if (angular.isDefined(data.row.table)) {
                view.name += data.row.table + '.';
            }
            view.name += data.row.name;

            view.description = data.row.description;

            if (angular.isDefined(data.row.type)) {
                view.type = data.row.type;
            }
            if (angular.isDefined(data.row.unit)) {
                view.unit = data.row.unit;
            }
            if (angular.isDefined(data.row.ucd)) {
                view.ucd = data.row.ucd;
            }

            if (data.row.order !== null) {
                view.order = data.row.order;
            }

            view.description = data.row.description;

            if (data.row.publication_role !== 'false') {
                view.publication_role = data.row.publication_role;
            }

            var permissions = [];
            if (data.row.publication_select === '1') {
                permissions.push('select');
            }
            if (data.row.publication_update === '1') {
                permissions.push('update');
            }
            if (data.row.publication_insert === '1') {
                permissions.push('insert');
            }
            view.permissions = permissions.join();

            if (data.row.publication_role !== 'false') {
                view.publication_role = data.row.publication_role;
            }

            // store database or table ids for later
            if (model == 'databases') {
                active.database_id = data.row.id;
            } else {
                active.database_id = data.row.database_id;
            }
            if (model == 'tables') {
                active.table_id = data.row.id;
            } else {
                active.table_id = data.row.table_id;
            }
        });
    }

    function fetchHtml(url) {
        $http.get(url,{'headers': {'Accept': 'application/html'}}).success(function(html) {
            for (var value in values) delete values[value];
            for (var error in errors) delete errors[error];

            ModalService.modal.html = html;

            if (url.indexOf('/data/tables/create') != -1 && angular.isDefined(active.database_id)) {
                values.database_id = active.database_id;
            }
            if (url.indexOf('/data/columns/create') != -1 && angular.isDefined(active.table_id)) {
                values.table_id = active.table_id;
            }

            active.url = url;
            ModalService.open();
        });
    }

    function submitForm(submit) {
        if (submit) {
            var data = {
                'csrf': angular.element('#csrf').attr('value')
            };

            // merge with form values
            angular.extend(data,values);

            $http.post(active.url,$.param(data)).success(function(response) {
                for (var error in errors) delete errors[error];

                if (response.status === 'ok') {
                    ModalService.close();

                    var m = active.url.match(/\/data\/(\w+)\/(\w+)/);
                    var model = m[1];
                    var action = m[2];

                    if (model === 'functions') {
                        BrowserService.initBrowser('functions');
                    } else {
                        BrowserService.initBrowser('databases');
                    }

                    if (action === 'update') {
                        var id = active.url.match(/\/(\d+)$/)[1];
                        var url = base + '/data/' + model + '/show/id/' + id;
                        fetchView(url);
                    } else {
                        for (var value in view) delete view[value];
                    }
                } else if (response.status === 'error') {
                    angular.forEach(response.errors, function(error, key) {
                        errors[key] = error;
                    });
                } else {
                    errors['form'] = {'form': ['Error: Unknown response from server.']};
                }
            });
        } else {
            ModalService.close();
        }
    }

    return {
        view: view,
        databases: BrowserService.browser.databases,
        functions: BrowserService.browser.functions,
        values: values,
        errors: errors,
        fetchView: fetchView,
        fetchHtml: fetchHtml,
        submitForm: submitForm
    };
}]);

app.controller('DataController', ['$scope','DataService',function($scope,DataService) {

    $scope.view = DataService.view;
    $scope.databases = DataService.databases;
    $scope.functions = DataService.functions;
    $scope.values = DataService.values;
    $scope.errors = DataService.errors;

    $scope.fetchHtml = function(event) {
        DataService.fetchHtml(event.target.href);
        event.preventDefault();
    };

    $scope.submitForm = function() {
        DataService.submitForm($scope.submit);
    };

    $scope.$on('browserItemClicked', function(event,browsername,url) {
        DataService.fetchView(url);
    });

}]);
