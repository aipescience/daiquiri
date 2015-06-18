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

angular.module('codemirror', [])

.directive('codemirror', ['CodemirrorService',function(CodemirrorService) {
    return {
        restrict: 'C', // only for the .codemirror class
        link: function(scope, elements) {
            elements.each(function(key,element) {
                var id = angular.element(element).attr('id');

                CodemirrorService.elements[id] = CodeMirror.fromTextArea(element, {
                    mode: 'text/x-mariadb',
                    indentWithTabs: false,
                    smartIndent: true,
                    matchBrackets : true,
                    lineNumbers: true,
                    lineWrapping: true,
                    autofocus: true
                });
                CodemirrorService.elements[id].setSize(angular.element(element).width(),null);
            });
        }
    };
}])

.factory('CodemirrorService', [function() {
    var elements = {};

    return {
        elements: elements,
        insert: function (key, string) {
            if (angular.isDefined(elements[key])) {
                var pos = elements[key].getCursor();
                pos['ch'] += string.length;
                elements[key].replaceSelection(string);
                elements[key].setCursor(pos);
                elements[key].focus();
            }
        },
        clear: function(key) {
            if (angular.isDefined(elements[key])) {
                elements[key].setValue('');
            }
        },
        refresh: function(key) {
            if (angular.isDefined(elements[key])) {
                elements[key].refresh();
            }
        },
        save: function(key) {
            if (angular.isDefined(elements[key])) {
                elements[key].save();
            }
        },
        setReadOnly: function(key) {
            if (angular.isDefined(elements[key])) {
                elements[key].readOnly = true;
            }
        }
    };
}]);
