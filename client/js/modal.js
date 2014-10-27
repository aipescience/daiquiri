var daiquiriModal = angular.module('modal', ['ngSanitize']);

daiquiriModal.directive('daiquiriModal', ['$compile','ModalService',function($compile,ModalService) {
    return {
        templateUrl: '/daiquiri/html/modal.html',
        link: function(scope, element) {
            scope.$watch(function () {
                return ModalService.modal.html;
            }, function(newValue, oldValue) {
                var element = $compile(newValue)(scope);
                angular.element('.daiquiri-modal-body').children().remove();
                angular.element('.daiquiri-modal-body').append(element);
                console.log('modal updated');
            }, true);
        }
    };
}]);

daiquiriModal.factory('ModalService', function() {
    var modal = {
        'enabled': false,
        'top': '100px',
        'width': '700px',
        'html': ''
    };

    return {
        modal: modal
    };
});

daiquiriModal.controller('ModalController', ['$scope','ModalService',function($scope,ModalService) {

    $scope.modal = ModalService.modal;

}]);
