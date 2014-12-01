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

angular.module('modal', ['ngSanitize'])

.directive('daiquiriModal', ['$compile','ModalService',function($compile,ModalService) {
    return {
        templateUrl: '/daiquiri/html/modal.html',
        link: function(scope, element) {
            scope.$watch(function () {
                return ModalService.modal.enabled;
            }, function(newValue, oldValue) {
                if (newValue === true) {
                    // parse html to a fake dom
                    var dom = angular.element(ModalService.modal.html);

                    // add ng-model to INPUT fields and set model to values
                    angular.forEach(angular.element('input',dom), function(node, key) {
                        var id = angular.element(node).attr('id');
                        var type = angular.element(node).attr('type');

                        // fo different things for different types
                        if (type === 'text') {
                            var element = angular.element('#' + id, dom);
                            element.attr('ng-model','values.' + id)
                            element.after('<ul class="unstyled text-error help-inline angular" ng-show="errors.' + id + '"><li ng-repeat="error in errors.' + id + '">{{error}}</li></ul>');

                            scope.values[id] = angular.element(node).attr('value');
                        } else if (type === 'checkbox') {
                            m = id.match(/(.*)\-(\d+)/);
                            angular.element('#' + id, dom).attr('ng-model','values.' + m[1] + '[' + m[2] + ']');

                            // prepare values array
                            if (angular.isUndefined(scope.values[m[1]])) {
                                scope.values[m[1]] = {};
                            }

                            // set values based on checked argument
                            if (angular.element(node).attr('checked') == 'checked') {
                                scope.values[m[1]][m[2]] = true;
                            } else {
                                scope.values[m[1]][m[2]] = false;
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
                        angular.element('#' + id, dom).attr('ng-model','values.' + id);

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

                    // compile to an angular element and add to actual modal
                    var element = $compile(dom)(scope);
                    angular.element('.daiquiri-modal-body').children().remove();
                    angular.element('.daiquiri-modal-body').append(element);
                }
            }, true);
        }
    };
}])

.factory('ModalService', ['$window',function($window) {
    var modal = {
        'enabled': false,
        'top': 100,
        'width': 660,
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
        if ($event.target === angular.element('.daiquiri-modal')[0]) {
            $scope.modal.enabled = false;
        };
    }

}]);
