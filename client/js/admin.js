var app = angular.module('admin',['table','modal','ngSanitize']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('AdminService', ['$http','ModalService','TableService',function($http,ModalService,TableService) {

    var values = {};
    var errors = {};
    var activeUrl = null;

    TableService.callback.rows = function(scope) {
        angular.element('.daiquiri-admin-option').on('click', scope.fetchHtml);
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
                        TableService.fetchRows();
                    } else if (response.status === 'error') {
                        angular.forEach(response.errors, function(error, key) {
                            errors[key] = error;
                        });
                    } else {
                        errors['form'] = {'form': ['Error: Unknown response from server.']};
                    }
                })
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
    }

    $scope.submitForm = function() {
        AdminService.submitForm($scope.submit);
    }
}]);