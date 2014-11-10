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

var app = angular.module('admin',['table','modal','ngSanitize']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('AdminService', ['$http','ModalService','TableService',function($http,ModalService,TableService) {

    var values = {};
    var errors = {};
    var activeUrl = null;

    // check if we have a paginated table or not
    var paginated = Boolean(angular.element('[daiquiri-table]').length);

    if (paginated) {
        TableService.callback.rows = function(scope) {
            angular.element('.daiquiri-admin-option').on('click', scope.fetchHtml);
        }
    }

    return {
        values: values,
        errors: errors,
        fetchHtml: function (url) {
            $http.get(url,{'headers': {'Accept': 'application/html'}}).success(function(html) {
                for (var value in values) delete values[value];
                for (var error in errors) delete errors[error];

                if (ModalService.modal.html != html) {
                    ModalService.modal.html = html;
                }

                activeUrl = url;
                ModalService.modal.enabled = true;
            });
        },
        submitForm: function(submit) {
            if (submit) {
                var data = {
                    'csrf': angular.element('#csrf').attr('value')
                };

                // merge with form values
                angular.extend(data,values);

                $http.post(activeUrl,$.param(data)).success(function(response) {
                    for (var error in errors) delete errors[error];

                    if (response.status === 'ok') {
                        ModalService.modal.enabled = false;

                        if (paginated) {
                            TableService.fetchRows();
                        } else {
                            console.log('hey');
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
                ModalService.modal.enabled = false;
            }
        }
    };
}]);

app.controller('AdminController', ['$scope','AdminService',function($scope,AdminService) {

    $scope.values = AdminService.values;
    $scope.errors = AdminService.errors;

    $scope.fetchHtml = function(event) {
        AdminService.fetchHtml(event.target.href);
        event.preventDefault();
    };

    $scope.submitForm = function() {
        AdminService.submitForm($scope.submit);
    };
}]);