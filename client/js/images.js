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

angular.module('images', [])

.factory('ImagesService', ['$http','$timeout','TableService',function($http,$timeout,TableService) {
    var values = {
        show: false
    };

    var meta = {}

    var base = angular.element('base').attr('href');

    function init(iCol,iRow) {
        meta.iCol = iCol;
        meta.iRow = iRow;

        values.colname = TableService.data.cols[iCol].name;
        show();
    }

    function show() {
        values.name = TableService.data.rows[meta.iRow].cell[meta.iCol];
        values.link = base + '/data/files/single/name/' + values.name;

        $timeout(function() {
            wheelzoom(document.querySelectorAll('.daiquiri-images img'));

            // hack for first image
            if (!values.show) {
                angular.element('.daiquiri-images img').on('load', function() {
                    angular.element('.daiquiri-images img').off('load');
                    values.show = true;
                });
            }
        });
    }

    function first() {
        // turn back table to page one
        TableService.first().then(function() {
            meta.iRow = 0;
            show();
        });
    }

    function prev() {
        if (meta.iRow % TableService.meta.nrows == 0) {
            // turn back table page
            TableService.prev().then(function() {
                meta.iRow = TableService.meta.nrows - 1;
                show();
            });
        } else {
            meta.iRow -= 1;
            show();
        }
    }

    function next() {
        if (meta.iRow % TableService.meta.nrows == TableService.meta.nrows - 1) {
            // turn over table page
            TableService.next().then(function() {
                meta.iRow = 0;
                show();
            });
        } else {
            meta.iRow += 1;
            show();
        }
    }

    function last() {
        // turn back table to the last page
        TableService.last().then(function() {
            meta.iRow = TableService.meta.nrows - 1;
            show();
        });
    }

    return {
        values: values,
        init: init,
        first: first,
        prev: prev,
        next: next,
        last: last
    };
}]);
