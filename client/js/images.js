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
        first: true
    };

    var meta = {}

    var base = angular.element('base').attr('href');

    function init() {
        meta.iCol = null;
        meta.iRow = null;

        values.name = null;
        values.link = null;

        values.colname = null;
        values.first = true;
    }

    function show(iCol,iRow) {
        meta.iCol = iCol;
        meta.iRow = iRow;

        values.colname = TableService.data.cols[iCol].name;
        values.first = true;
        refresh();
    }

    function refresh() {
        values.name = TableService.data.rows[meta.iRow].cell[meta.iCol];
        values.link = base + '/data/files/single/name/' + values.name;
        values.row_id = TableService.data.rows[meta.iRow].cell[0];

        $timeout(function() {
            wheelzoom(document.querySelectorAll('.daiquiri-images img'));

            if (values.first) {
                $timeout(function() {
                    values.first = false;
                }, 500);
            }
        });
    }

    function first() {
        // turn back table to page one
        TableService.first().then(function() {
            meta.iRow = 0;
            refresh();
        });
    }

    function prev() {
        if (meta.iRow % TableService.meta.nrows == 0) {
            // turn back table page
            TableService.prev().then(function() {
                meta.iRow = TableService.meta.nrows - 1;
                refresh();
            });
        } else {
            meta.iRow -= 1;
            refresh();
        }
    }

    function next() {
        if (meta.iRow % TableService.meta.nrows == TableService.meta.nrows - 1) {
            // turn over table page
            TableService.next().then(function() {
                meta.iRow = 0;
                refresh();
            });
        } else {
            meta.iRow += 1;
            refresh();
        }
    }

    function last() {
        // turn back table to the last page
        TableService.last().then(function() {
            meta.iRow = TableService.meta.nrows - 1;
            refresh();
        });
    }

    return {
        values: values,
        init: init,
        show: show,
        first: first,
        prev: prev,
        next: next,
        last: last
    };
}]);
