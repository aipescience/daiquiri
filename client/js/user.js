var app = angular.module('user',['table','modal','ngSanitize']);

app.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.headers.common['Accept'] = 'application/json';
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
}]);

app.factory('UserService', ['$http','$timeout','ModalService','TableService',function($http,$timeout,ModalService,TableService) {

    var values = {};
    var errors = {};
    var activeUrl = null;

    // initialize table
    TableService.url.cols = '/auth/user/cols';
    TableService.url.rows = '/auth/user/rows';
    TableService.callback.rows = function(scope) {
        angular.element('.daiquiri-user-option').on('click', scope.fetchHtml);
    }
    TableService.init();

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
                    switch (response.status) {
                        case 'ok': 
                            ModalService.modal.enabled = false;
                            TableService.fetchRows();
                        case 'error':
                            for (var error in errors) delete errors[error];
                            angular.forEach(response.errors, function(object, key) {
                                errors[key] = object;
                            });
                        default:
                            errors = {'form': ['Error: Unknown response from server.']};
                    }
                })
            } else {
                ModalService.modal.enabled = false;
            }
        }
    };
}]);

app.controller('UserController', ['$scope','UserService',function($scope,UserService) {

    $scope.values = UserService.values;
    $scope.errors = UserService.errors;

    $scope.fetchHtml = function(event) {
        UserService.fetchHtml(event.target.href);
        event.preventDefault();
    }
    $scope.submitForm = function() {
        UserService.submitForm($scope.submit);
    }

}]);