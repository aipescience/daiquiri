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
                return ModalService.modal.html;
            }, function(newValue, oldValue) {
                var element = $compile(newValue)(scope);
                angular.element('.daiquiri-modal-body').children().remove();
                angular.element('.daiquiri-modal-body').append(element);
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
