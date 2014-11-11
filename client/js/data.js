/*  
 *  Copyright (c) 2012-2014  Jochen S. Klar <jklar@aip.de>,
 *                            Adrian M. Partl <apartl@aip.de>, 
 *                            AIP E-Science (www.aip.de)
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

    var values = {};
    var errors = {};
    var activeUrl = null;

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

                        if (table) {
                            TableService.fetchRows();
                        } else {
                            $window.location.reload();
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

app.controller('DataController', ['$scope','DataService',function($scope,DataService) {

    $scope.values = DataService.values;
    $scope.errors = DataService.errors;

    $scope.fetchHtml = function(event) {
        DataService.fetchHtml(event.target.href);
        event.preventDefault();
    };

    $scope.submitForm = function() {
        DataService.submitForm($scope.submit);
    };
}]);