/*  
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
 
/*
 * daiquiri_plot - a plugin fo jquery and flot
 * 
 * (c) Jochen Klar and AIP E-Science 2012
 *
 */

var _daiquiri_plot = {
    defaults: {

    },
    items: {}
}

function Daiquiri_Plot(container, opt) {
    // set state
    this.container = container;
    this.opt = opt;
 
    this.ajaxError = function (jqXHR, textStatus, errorThrown) {
        var message = 'Error with ajax call';
        console.log(message);
        console.log({
            'status': jqXHR.status,
            'statusText': jqXHR.statusText,
            'responseText': jqXHR.responseText
        });
    };
    
    this.displayForm = function () {
        var self = this;
        
        // get colums by ajax call 
        $.ajax({
            url: self.opt.colsurl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                'db': self.opt.params.db,
                'table': self.opt.params.table
            },
            error: self.ajaxError,
            success: function (json) {
                var selectOptions = '';
                for (var i = 0; i < json.cols.length; i++) {
                    var col = json.cols[i].name;
                    selectOptions += '<option label="' + col + '"value="'+ col +'">' + col + '</option>'
                }
                
                var html = '<h4>Select Colums for plot</h4>';
                html += '<p>';
                html += '<label for="xCol" class="required">Column for X axis:</label>'
                html += '<select id="xCol" name="xCol" value="1">';
                html += selectOptions;
                html += '</select>';
                html += '<label for="yCol" class="required">Column for Y axis:</label>'
                html += '<select id="yCol" name="yCol" value="2">';
                html += selectOptions;
                html += '</select>';
                html += '<label for="number" class="required">Number of rows to plot:</label>'
                html += '<input type="text" name="number" id="number" value="1000">'
                html += '</p><p>';
                html += '<button id="submitplot" value="Create Plot" class="btn btn-primary">Create Plot</button>'
                html += '</p>'

                $('<div/>',{
                    'html' : html
                }).appendTo(self.opt.form);
                
                if (json.cols.length > 2) {
                    $("#xCol option[value='" + json.cols[1].name + "']").attr('selected',true);
                    $("#yCol option[value='" + json.cols[2].name + "']").attr('selected',true);
                } else if (json.cols.length == 2) {
                    $("#yCol option[value='" + json.cols[1].name + "']").attr('selected',true);
                }
                
                $('#submitplot').click(function(){
                    self.displayPlot($('#xCol').val(),$('#yCol').val(),$('#number').val());
                });
            }
        });
    };
    this.displayPlot = function (xCol, yCol, number) {
        var self = this;
        // get colums by ajax call 
        $.ajax({
            url: self.opt.rowsurl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                'db': self.opt.params.db,
                'table': self.opt.params.table,
                'nrows': number,
                'cols': xCol + ',' + yCol
            },
            error: self.ajaxError,
            success: function (json) {
                var x,y;
                var plotData = [];

                // gather data for plot
                for (var i = 0; i < json.rows.length; i++) {
                    x = json.rows[i][0];
                    if (json.rows[i].length > 1) {
                        y = json.rows[i][1];
                    } else {
                        y = x;
                    }
                    plotData.push([x,y]);
                }

                var series = {
                    color: "#900",
                    data: plotData
                }
  
                var opt = {
                    color: "#900",
                    lines: {
                        show: false
                    },
                    points: {
                        radius: 0.5,
                        show: true,
                        fill: true,
                        fillColor: "#900"
                
                    },
                    shadowSize: 0,
                    xaxis: {
                        tickFormatter: self.tickFormatter
                    },
                    yaxis: {
                        tickFormatter: self.tickFormatter
                    }
                };

                // draw plot
                $.plot(self.container, [series], opt);
            }
        });
    };
    
    /**
     * Tick formatter to format ticks.
     */
    this.tickFormatter = function(val, axis) {
        if (val > 1000) {
            var exp = Math.floor(Math.log(val) / Math.LN10);
            var man = val / Math.pow(10,exp);
            return (man).toFixed(3) + "E" + exp;
        } else {
            return val.toFixed(axis.tickDecimals);
        }
    }
    
    /**
     * Resets the container to the inital state
     */
    this.reset = function() {       
        this.container.children().remove();
        this.opt.form.children().remove();
    };
}

// main plugin
(function($){
    $.fn.extend({ 
        daiquiri_plot: function(opt) {
            
            // apply default options
            opt = $.extend(_daiquiri_plot.defaults, opt);

            return this.each(function() {
                var id = $(this).attr('id');

                // check if plot is already set
                if (_daiquiri_plot.items[id] == undefined) {
                    _daiquiri_plot.items[id] = new Daiquiri_Plot($(this),opt);
                } else {
                    _daiquiri_plot.items[id].reset();
                    _daiquiri_plot.items[id].opt = opt;
                }
                
                _daiquiri_plot.items[id].displayForm();
            });  
        }
    });
})(jQuery);
