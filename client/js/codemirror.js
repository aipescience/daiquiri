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

angular.module('codemirror', [])

.directive('codemirror', ['CodemirrorService',function(CodemirrorService) {
    return {
        restrict: 'C', // only for the .codemirror class
        link: function(scope, elements) {
            elements.each(function(key,element) {
                CodemirrorService.elements[key] = CodeMirror.fromTextArea(element, {
                    mode: 'text/x-mysql',
                    indentWithTabs: false,
                    smartIndent: true,
                    matchBrackets : true,
                    lineNumbers: true,
                    lineWrapping: true,
                    autofocus: true
                });
                CodemirrorService.elements[key].setSize(angular.element(element).width(),null);
            });
        }
    };
}])

.factory('CodemirrorService', [function() {
    var elements = {};

    return {
        elements: elements,
        insert: function (string, key) {
            if (angular.isUndefined(key)) key = 0;

            var pos = elements[key].getCursor();
            pos['ch'] += string.length;
            elements[key].replaceSelection(string);
            elements[key].setCursor(pos);
            elements[key].focus();
        },
        clear: function(key) {
            if (angular.isUndefined(key)) key = 0;

            elements[key].setValue('');
        },
        refresh: function(key) {
            if (angular.isUndefined(key)) key = 0;

            elements[key].refresh();
        },
        save: function() {
            angular.forEach(elements,function(object, key) {
                object.save();
            });
        }
    };
}]);
