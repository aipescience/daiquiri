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

angular.module('modal', ['ngSanitize'])

.directive('daiquiriModal', ['$compile','ModalService',function($compile,ModalService) {
    return {
        restrict: 'A',
        transclude: true,
        templateUrl: angular.element('base').attr('href') + '/daiquiri/html/modal.html',
        link: function(scope, element) {
            if (angular.isUndefined(element.attr('transclude'))) {
                scope.$watch(function () {
                    return ModalService.modal.enabled;
                }, function(newValue, oldValue) {
                    if (newValue === true) {
                        // parse html to a fake dom
                        var dom = angular.element(ModalService.modal.html);

                        // remove the action attribute from the form
                        angular.element('form', dom).removeAttr('action');

                        // add errors to form
                        angular.element('#fieldset-actiongroup', dom).before('<ul class="unstyled text-error align-form-horizontal" ng-show="errors.form"><li ng-repeat="error in errors.form">{{error}}</li></ul>');

                        // add ng-model to INPUT fields and set model to values
                        angular.forEach(angular.element('input',dom), function(node, key) {
                            var id = angular.element(node).attr('id');
                            var type = angular.element(node).attr('type');

                            // fo different things for different types
                            if (type === 'text' || type == 'password') {
                                var element = angular.element('#' + id, dom);
                                element.attr('ng-model','values.' + id)
                                element.after('<ul class="unstyled text-error" ng-show="errors.' + id + '"><li ng-repeat="error in errors.' + id + '">{{error}}</li></ul>');

                                scope.values[id] = angular.element(node).attr('value');
                            } else if (type === 'checkbox') {
                                var element = angular.element('#' + id, dom);
                                var hidden = angular.element("[name='" + id + "']",dom);

                                if (hidden.length >= 2) {
                                    // this is a regular single with a hidden field checkbox
                                    var element = angular.element('#' + id, dom);
                                    element.attr('ng-model','values.' + id)
                                    element.attr('ng-true-value','1');
                                    element.attr('ng-false-value','0');

                                    if (element.attr('checked') == 'checked') {
                                        scope.values[id] = 1;
                                    } else {
                                        scope.values[id] = 0;
                                    }

                                } else {
                                    // this belongs to a multiple checkbox
                                    var group = id.match(/^([a-zA-Z0-9]*)/)[0];
                                    var value = element.attr('value');

                                    element.attr('ng-model','values.' + group + "['" + value + "']");

                                    // prepare values array
                                    if (angular.isUndefined(scope.values[group])) {
                                        scope.values[group] = {};
                                    }

                                    // set values based on checked argument
                                    if (angular.element(node).attr('checked') == 'checked') {
                                        scope.values[group][value] = true;
                                    } else {
                                        scope.values[group][value] = false;
                                    }
                                }

                            } else if (type === 'radio') {
                                m = id.match(/(.*)\-\d+/);
                                angular.element('#' + id, dom).attr('ng-model','values.' + m[1]);

                                if (angular.element(node).attr('checked') == 'checked') {
                                    scope.values[m[1]] = parseInt(angular.element(node).attr('value'));
                                }
                            }

                        });

                        // add ng-model to SELECT fields and set model to values
                        angular.forEach(angular.element('select',dom), function(node, key) {
                            var id = angular.element(node).attr('id');
                            var element = angular.element('#' + id, dom);
                            element.attr('ng-model','values.' + id);
                            element.after('<ul class="unstyled text-error" ng-show="errors.' + id + '"><li ng-repeat="error in errors.' + id + '">{{error}}</li></ul>');

                            if (angular.element(node).attr('multiple')) {
                                // this is a multiselect
                                scope.values[id] = [];
                                angular.element('option[selected="selected"]',node).each(function (key, element) {
                                    var value = angular.element(element).attr('value');
                                    scope.values[id].push(value);
                                });
                            } else {
                                // this is a regular select
                                scope.values[id] = angular.element('option[selected="selected"]',node).attr('value');
                            }
                        });

                        // add ng-model to TEXTAREA fields and set model to values
                        angular.forEach(angular.element('textarea',dom), function(node, key) {
                            var id = angular.element(node).attr('id');
                            var element = angular.element('#' + id, dom);
                            element.attr('ng-model','values.' + id);
                            element.after('<ul class="unstyled text-error" ng-show="errors.' + id + '"><li ng-repeat="error in errors.' + id + '">{{error}}</li></ul>');

                            scope.values[id] = angular.element(node).text();
                        });

                        // compile to an angular element and add to actual modal
                        var element = $compile(dom)(scope);
                        angular.element('.daiquiri-modal-body').children().remove();
                        angular.element('.daiquiri-modal-body').append(element);
                    }
                }, true);
            }
        }
    };
}])

.factory('ModalService', ['$window',function($window) {
    var modal = {
        'enabled': false,
        'top': 100,
        'width': 800,
        'maxHeight': $window.innerHeight - 100 - 100,
        'html': ''
    };

    return {
        modal: modal
    };
}])

.controller('ModalController', ['$scope','ModalService',function($scope,ModalService) {

    $(window).resize(function() {
        $scope.$apply(function() {
            $scope.modal.maxHeight = window.innerHeight - 100 - 100;
        });
    });

    // enable esc key
    $(document).keyup(function(e) {
        if (e.keyCode == 27) {
            $scope.$apply(function() {
                $scope.modal.enabled = false;
            });
            return false;
        }
    });

    $scope.modal = ModalService.modal;

    $scope.closeModal = function($event) {
        if (angular.isUndefined($event)) {
            $scope.modal.enabled = false;
        };
        if (angular.element($event.target).hasClass('daiquiri-modal')) {
            $scope.modal.enabled = false;
        };
    }

}]);
