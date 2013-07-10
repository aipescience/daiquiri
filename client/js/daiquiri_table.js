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
 * daiquiri_table - a plugin fo jquery and bootstap
 * 
 */

var _daiquiri_table = {
    defaults: {
        'params': {},
        'rowNum': 20,
        'rowList': [20,100,1000],
        'colswidth': '100px',
        'shrinkToFit': false,
        'hoverrows': false,
        'sortable': null,
        'width': null,
        'height': null,
        'select': null,
        'edit': false,
        'del': false,
        'add': false,
        'editinline': null,
        'callback': null
    },
    items: {}
}

function Daiquiri_Table(container, opt) {
    // set state
    this.container = container;
    this.opt = opt;

    this.tableId = this.container.attr('id') + '-table';
    this.pagerId = this.container.attr('id') + '-pager';

    // create table
    this.table = $('<table/>',{
        'id': this.tableId 
    }).appendTo(this.container);

    // create pager
    this.pager = $('<div/>',{
        'id': this.pagerId
    }).appendTo(this.container);

    // set the dimensions of the container
    if (opt.width) {
        this.container.width(opt.width);
    }
    if (opt.height) {
        this.container.height(opt.height);
    }

    // set class
    this.container.addClass('daiquiri-table');

    /**
     * Function to display the jqGrid table
     */
    this.displayGrid = function (json) {
        var self = this;
        var colsmodel = json.data;

        /* create cols model */
        var cols = [];
        $.each(colsmodel, function() {
            cols.push(this.name);
            if (this.formatter == 'singleFileLink' && self.opt.select !== false) {
                self.opt.select = true;
            }
        });

        var lastid;
        this.table.jqGrid({
            url: self.opt.rowsurl,
            postData: self.opt.params,
            datatype: "json",
            colNames: cols,
            colModel: colsmodel,
            rowNum: self.opt.rowNum,
            rowList: self.opt.rowList,
            viewrecords: true,
            pager: self.pagerId,
            sortorder: "asc",
            caption: self.opt.tablename,
            shrinkToFit: self.opt.shrinkToFit,
            hoverrows: self.opt.hoverrows,
            multiselect: self.opt.select,
            editurl: self.opt.editurl + '?table=' + self.opt.tablename,
            jsonReader : {
                root: "data.rows",
                page: "data.page",
                total: "data.total",
                records: "data.records"
            },
            loadBeforeSend: function(jqXHR) {
                jqXHR.setRequestHeader("Accept", 'application/json');
            },
            onSelectRow: function(id){
                if (self.opt.editinline) {
                    if(id && id!==lastid){
                        $(this).jqGrid('restoreRow',lastid);
                        $(this).jqGrid('editRow',id,{
                            keys: true,
                            extraparam: self.opt.params
                        });
                        lastid=id;
                    }
                }
            },
            gridComplete: function() {
                // resize the the grid when it is fully loaded
                self.resize();
                if (self.opt.callback) {
                    self.opt.callback(self);
                }
            },
            resizeStop: function(width, index) { 
                // resize the grid when a column is resized
                self.resize();
            }
        }).navGrid('#'+this.pagerId,{
            edit: self.opt.edit,
            del: self.opt.del,
            add: self.opt.add
        },
        {
            // params for editing
            editData: this.opt.params,
            closeAfterEdit: true
        },
        {
            // params for adding
            editData: this.opt.params,
            closeAfterAdd: true
        },
        {
            // params for deleting
            delData: this.opt.params
        },{
            // params for searching
            closeAfterSearch: true,
            closeOnEscape: true
        },{});
                                                                                    
        // remove the collapse button
        $('.ui-jqgrid-titlebar-close','#gview_'+ this.tableId).remove();
    };
        
    /**
     * Function to resize the table to fit into container
     */
    this.resize = function() {
        // calculate width
        var w;
        var outerWidth = this.container.width() - 2; // - 2px for borders
        var innerWidth = $('.ui-jqgrid-bdiv').get(0).scrollWidth;
        if (this.opt.width !== null) {
            // fixed width, subtract 2px for 2 vertical borders
            w = outerWidth;
            $('.ui-jqgrid-bdiv').css('overflow-x','auto');
        } else {
            // adjust width to match container
            if (innerWidth <= 500) {
                w = 500;
                $('.ui-jqgrid-bdiv').css('overflow-x','hidden');
            } else if (innerWidth < outerWidth) {
                w = innerWidth;
                $('.ui-jqgrid-bdiv').css('overflow-x','hidden');
                $('th:last-child',this.container).css('borderRightWidth','0px');
                $('td:last-child',this.container).css('borderRightWidth','0px');
            } else {
                w = outerWidth;
                $('.ui-jqgrid-bdiv').css('overflow-x','auto');
                $('th:last-child',this.container).css('borderRightWidth','0px');
                $('td:last-child',this.container).css('borderRightWidth','0px');
            }
        }

        // calculate height
        var h;
        if (this.opt.height !== null) {
            // (4px for 4 horizontal borders)
            h = this.container.height() 
            - $('.ui-jqgrid-hdiv',this.container).height()
            - this.pager.height() - 4;
        } else {
            if (this.table.height() > $('.ui-jqgrid-hdiv',this.container).height()) {
                h = this.table.height();
            } else {
                // only one row
                h = $('.ui-jqgrid-hdiv',this.container).height();
            }
            if (innerWidth > w) {
                // 15px for the horizontal scrollbar
                h += 15;
            } else {
                // 1px for the bottom border
                h -= 1;
            }
            $('.ui-jqgrid-bdiv').css('overflow-y','hidden');
        }

        this.table.setGridWidth(w);
        this.table.setGridHeight(h);
    };
        
    /**
     * Resets the container to the inital state
     */
    this.reset = function() {
        this.table.GridUnload();
            
        this.container.children().remove();
            
        // create table
        this.table = $('<table/>',{
            'id': this.tableId 
        }).appendTo(this.container);

        // create pager
        this.pager = $('<div/>',{
            'id': this.pagerId
        }).appendTo(this.container);
    };
}

/* Extend jQuery formatters for links */
jQuery.extend($.fn.fmatter, {
    singleFileLink: function(cellvalue, options, rowdata) {
        if(typeof options.colModel.formatoptions.baseLinkUrl != 'undefined') {
            if(cellvalue != null) {
                var re = /(?:\.([^.]+))?$/;
                var ext = re.exec(cellvalue)[1];
                ext = ext.toLowerCase();

                if(ext == 'jpg' || ext == 'jpeg' || ext == 'png' || ext == 'bmp') {
                    return '<a href="' + options.colModel.formatoptions.baseLinkUrl + '?name=' + cellvalue + '" target="_blank">' +
                    cellvalue + '</a>';
                } else {
                    return '<a href="' + options.colModel.formatoptions.baseLinkUrl + '?name=' + cellvalue + '">' +
                    cellvalue + '</a>';
                }
            } else {
                return '';
            }
        } else {
            return cellvalue;            
        }
    },
    removeNewline: function(cellvalue, options, rowdata) {
        if (cellvalue) {
            cellvalue = cellvalue.replace(/(\r\n|\n|\r)/gm,"");
        }
        return cellvalue
    }
});

// main plugin
(function($){
    $.fn.extend({ 
        daiquiri_table: function(opt) {
            
            // apply default options
            opt = $.extend({},_daiquiri_table.defaults, opt);

            return this.each(function() {
                var id = $(this).attr('id');

                // check if table is already set
                if (_daiquiri_table.items[id] == undefined) {
                    _daiquiri_table.items[id] = new Daiquiri_Table($(this),opt);
                } else {
                    _daiquiri_table.items[id].reset();
                    _daiquiri_table.items[id].opt = opt;
                }
                
                // json ajax request for the table metadata
                $.ajax({
                    url: opt.colsurl, 
                    data: opt.params,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json'
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        var message = 'Error with ajax request';
                        console.log(message);
                        console.log({
                            'jqXHR': jqXHR,
                            'textStatus': textStatus,
                            'errorThrown': errorThrown
                        });
                        alert(message);
                    },
                    success: function(json) {
                        if (json.status != 'ok') {
                            var message = 'Error with ajax request';
                            console.log(message);
                            console.log(json);
                            alert(message);
                        }
                        else {
                            _daiquiri_table.items[id].displayGrid(json);
                            $(window).bind('resize', function() {
                                setTimeout("_daiquiri_table.items['"+id+"'].resize();",100);
                            });
                            setTimeout("_daiquiri_table.items['"+id+"'].resize();",100);
                        }
                    }
                });
            });  
        },
        daiquiri_table_resize: function(opt) {
            
            // apply default options
            opt = $.extend(_daiquiri_table.defaults, opt);

            return this.each(function() {
                var id = $(this).attr('id');
                
                // resize table if it is there
                if (_daiquiri_table.items[id] != undefined) {
                    console.log(_daiquiri_table.items[id].resize);
                    _daiquiri_table.items[id].resize();
                }
            });  
        }
    });
})(jQuery);
