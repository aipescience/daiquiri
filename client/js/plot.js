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

angular.module('plot', [])

.factory('PlotService', ['$http',function($http) {
    var values = {};
    var errors = {};
    var labels = {};

    var base = angular.element('base').attr('href');

    // initial values
    values.plot_nrows = 100;
    values.plot_x_scale = 'lin';
    values.plot_y_scale = 'lin';

    // function to format the tics
    function tickFormatter(val, axis) {
        if (val > 1000) {
            var exp = Math.floor(Math.log(val) / Math.LN10);
            var man = val / Math.pow(10,exp);
            return (man).toFixed(3) + "E" + exp;
        } else {
            return val.toFixed(axis.tickDecimals);
        }
    };

    return {
        values: values,
        errors: errors,
        labels: labels,
        init: function(account) {
            values.db = account.job.database;
            values.table = account.job.table;
            values.plot_x = account.job.cols[1];
            values.plot_y = account.job.cols[2];

            $('#plot-canvas').children().remove();
            for (var label in labels) delete labels[label];
        },
        createPlot: function() {
            for (var error in errors) delete errors[error];

            // manual validation
            var valid = true;
            if (angular.isUndefined(values.plot_x)) {
                errors.plot_x = ['Please select a column'];
                valid = false;
            }
            if (angular.isUndefined(values.plot_y)) {
                errors.plot_y = ['Please select a column'];
                valid = false;
            }

            // parse ranges
            angular.forEach(['plot_x_min','plot_x_max'], function(key) {
                if (values[key] === '') delete values[key];

                if (!angular.isUndefined(values[key])) {
                    var f = parseFloat(values[key]);
                    if (isNaN(f)) {
                        errors.plot_x_range = ['Please give a numerial value'];
                        valid = false;
                    } else {
                        values[key] = f;
                    }
                }
            });
            angular.forEach(['plot_y_min','plot_y_max'], function(key) {
                if (values[key] === '') delete values[key];

                if (!angular.isUndefined(values[key])) {
                    var f = parseFloat(values[key]);
                    if (isNaN(f)) {
                        errors.plot_y_range = ['Please give a numerial value'];
                        valid = false;
                    } else {
                        values[key] = f;
                    }
                }
            });

            // return if validation fails
            if (valid === false) return;

            // obtain the data from the server
            $http.get(base + '/data/viewer/rows/',{
                'params': {
                    'db': values.db,
                    'table': values.table,
                    'cols': values.plot_x.name + ',' + values.plot_y.name,
                    'nrows': values.plot_nrows
                }
            }).success(function(response) {
                if (response.status == 'ok') {
                    var data = [];
                    for (var i=0; i<response.nrows; i++) {
                        data.push([response.rows[i].cell[0],response.rows[i].cell[1]]);
                    }

                    // create plot
                    var options = {
                        lines: {
                            show: false
                        },
                        points: {
                            radius: 1,
                            show: true,
                            fill: true
                        },
                        shadowSize: 0,
                        xaxis: {
                            tickFormatter: tickFormatter,
                            label: 'fff'
                        },
                        yaxis: {
                            tickFormatter: tickFormatter
                        }
                    };

                    if (!angular.isUndefined(values.plot_x_min)) options.xaxis.min = values.plot_x_min;
                    if (!angular.isUndefined(values.plot_x_max)) options.xaxis.max = values.plot_x_max;
                    if (!angular.isUndefined(values.plot_y_min)) options.yaxis.min = values.plot_y_min;
                    if (!angular.isUndefined(values.plot_y_max)) options.yaxis.max = values.plot_y_max;

                    if (values.plot_x_scale === 'log') options.xaxis.transform = function(v) {return Math.log(v+0.0001)};
                    if (values.plot_y_scale === 'log') options.yaxis.transform = function(v) {return Math.log(v+0.0001)};

                    $.plot('#plot-canvas', [{
                        color: "#08c",
                        data: data
                    }],options);

                    // set axes label
                    labels.x = values.plot_x.name;
                    if (values.plot_x.unit.length > 0) labels.x += ' [' + values.plot_x.unit + ']';

                    labels.y = values.plot_y.name;
                    if (values.plot_y.unit.length > 0) labels.y += ' [' + values.plot_y.unit + ']';

                } else {
                    errors.form = ['There was a problem receiving the data.']
                }
            }).error(function () {
                errors.form = ['Could not connect to server.']
            });
        }
    };
}]);
