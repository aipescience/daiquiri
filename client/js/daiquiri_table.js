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

// namespaces
var daiquiri = daiquiri || {};
daiquiri.table = {};

/**
 * jquery plugin to insert a table in a given jquery selection
 */
(function($){
    $.fn.extend({ 
        daiquiri_table: function(opt) {
            opt = $.extend({}, daiquiri.table.opt, opt);
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (daiquiri.table.items[id] == undefined) {
                    daiquiri.table.items[id] = new daiquiri.table.Table($(this),opt);
                } else {
                    daiquiri.table.items[id].reset(opt);
                }
            });
        }
    });
})(jQuery);

/**
 * Object to hold the different instances of the Table class.
 */
daiquiri.table.items = {};

/**
 * Object to hold the information to resize a column.
 */
daiquiri.table.resizing = null;

/**
 * Object to hold the default option of the table.
 */
daiquiri.table.opt = {
    'header' : {},
    'nrows': 10,
    'nrowsList': [10,100],
    'sort': null,
    'columnWidth': '100px',
    'multiselect': false
};

/**
 * Constructor-like function for the Table class. 
 */
daiquiri.table.Table = function(container, opt) {
    this.container = container;
    this.id = container.attr('id');
    this.opt = opt;
    params = null;
    pages = null;
    ncols = null;
    colsmodel = null;

    // create pager
    $('<div/>',{
        'class': 'daiquiri-table-pager'
    }).appendTo(this.container);
    
    // create pane for table
    $('<div/>',{
        'class': 'daiquiri-table-pane',
        'html': '<table class="table"><thead></thead><tbody></tbody></table>'
    }).appendTo(this.container);

    // create message
    $('<div/>',{
        'class': 'daiquiri-table-message'
    }).appendTo(this.container);

    // set class
    this.container.addClass('daiquiri-table');

    // initial params
    this.params = {
        'nrows': this.opt.nrows,
        'page': 1,
        'sort': this.opt.sort,
        'search': null
    };

    // display table
    this.pager();
    this.cols();
};

/**
 * Resets the table to its inital state and overwrites the opt object.
 */
daiquiri.table.Table.prototype.reset = function (opt) {
    this.opt = opt;

    $('thead','.daiquiri-table-pane', this.container).children().remove();
    $('tbody','.daiquiri-table-pane', this.container).children().remove();
    $('.daiquiri-table-pager', this.container).children().remove();
    $('.daiquiri-table-message', this.container).children().remove();
    
    // initial params
    this.params = {
        'nrows': this.opt.nrows,
        'page': 1,
        'sort': this.opt.sort,
        'search': null
    };
    
    // display table
    this.pager();
    this.cols();
}

/**
 * Constructs and displays the pager at the bottom of the table.
 */
daiquiri.table.Table.prototype.pager = function () {
    var self = this;

    var searchHtml = '<input placeholder="Search" type="text" class="input-mini" />';
    searchHtml += '<button class="btn"><i class="icon-search"></i></button>';
    var search = $('<form/>',{
        'id': self.id + '-pager-search',
        'class': 'daiquiri-table-pager-search-form input-append pull-left',
        'html': searchHtml
    }).submit(function () {
        var id = $(this).attr('id').match(/(.+)-pager-search/)[1];
        var self = daiquiri.table.items[id];
        self.params.search = $('input',this).val()
        self.rows();
        return false;
    }).appendTo($('.daiquiri-table-pager', self.container));

    $('#' + self.id + '-pager-search').click(function () {
        $(this).parent().submit();
    });

    var html = '<ul>';
    html += '<li><a id="' + self.id + '-pager-first" href="#">First</a></li>';
    html += '<li><a id="' + self.id + '-pager-prev" href="#">Previous</a></li>';
    html += '<li><a id="' + self.id + '-pager-next" href="#">Next</a></li>';
    html += '<li><a id="' + self.id + '-pager-last" href="#">Last</a></li>';
    html += '</ul>';

    $('<div />', {
        'id': self.id + '-pager-pagination',
        'class': 'pagination pull-left',
        'html': html
    }).appendTo($('.daiquiri-table-pager', self.container));
    
    $('<div />', {
        'id': self.id + '-pager-reset',
        'class': 'pagination pull-left',
        'html': '<ul><li><a href="#">Reset</a></li></ul>'
    }).appendTo($('.daiquiri-table-pager', self.container));

    $('#' + self.id + '-pager-first').click(function () {
        var id = $(this).attr('id').match(/(.+)-pager-first/)[1];
        var self = daiquiri.table.items[id];
        self.params.page = 1;
        self.rows();
        return false;
    });
    
    $('#' + self.id + '-pager-prev').click(function () {
        var id = $(this).attr('id').match(/(.+)-pager-prev/)[1];
        var self = daiquiri.table.items[id];
        self.params.page -= 1; 
        if (self.params.page < 1) {
            self.params.page = 1;
        }
        self.rows();
        return false;
    });

    $('#' + self.id + '-pager-next').click(function () {
        var id = $(this).attr('id').match(/(.+)-pager-next/)[1];
        var self = daiquiri.table.items[id];
        self.params.page += 1;
        if (self.params.page > self.pages) {
            self.params.page = self.pages;
        }
        self.rows();
        return false;
    });
    
    $('#' + self.id + '-pager-last').click(function () {
        var id = $(this).attr('id').match(/(.+)-pager-last/)[1];
        var self = daiquiri.table.items[id];
        self.params.page = self.pages;
        self.rows();
        return false;
    });

    $('#' + self.id + '-pager-reset').click(function () {
        var id = $(this).attr('id').match(/(.+)-pager-reset/)[1];
        var self = daiquiri.table.items[id];
        
        $('input','.daiquiri-table-pager-search-form', self.container).val('');
            
        self.params.search = null
        self.params.sort = null
        self.params.page = 1
        self.rows();
        return false;
    });

    $('<div/>',{
        'class': 'daiquiri-table-pager-paging pull-left',
        'id': self.id + '-pager-paging'
    }).appendTo($('.daiquiri-table-pager', self.container));

    var select = $('<select/>',{
        'size': self.opt.nrowsList.length,
        'class': 'daiquiri-table-pager-nrows pull-right',
        'id': self.id + '-pager-nrows'
    }).appendTo($('.daiquiri-table-pager', self.container));

    $.each(self.opt.nrowsList, function (key, value) {
        var option = {
            'value': value,
            'html': 'Show ' + value + ' rows'
        };
        if (self.opt.nrows == value) {
            option.selected = 'selected';
        }
        $('<option/>', option).appendTo(select);
    });

    $('#' + self.id + '-pager-nrows').change( function() {
        var id = $(this).attr('id').match(/(.+)-pager-nrows/)[1];
        var self = daiquiri.table.items[id];
        self.params.page = 1;
        self.params.nrows = $(this).val();
        self.rows();
    });
}

/**
 * Gets the colums by ajax and constructs the th elements. On success it calls the rows function.
 */
daiquiri.table.Table.prototype.cols = function () {
    var self = this;

    // get the cols via ajax
    $.ajax({
        url: self.opt.colsurl, 
        type: 'GET',
        data: $.extend({}, self.opt.params, self.params),
        dataType: 'json',
        headers: $.extend({}, self.opt.header, {
            'Accept': 'application/json'
        }),
        error: daiquiri.common.ajaxError,
        success: function (json) {
            if (json.status == 'ok') {
                self.ncols = json.cols.length;
                self.colsmodel  = json.cols;

                // contruct and append html elements for column headers
                var html = '<tr>';
                var width,colId;
                for (var i = 0; i < self.ncols; i++) {
                    // get the id of the column
                    colId = self.colsmodel[i]["id"];
                    //if (typeof colId === 'undefined') colId = i;

                    if (self.colsmodel[i].hidden != true) {
                        if (self.colsmodel[i].width != undefined) {
                            width = self.colsmodel[i].width;
                        } else {
                            width = self.opt.colsWidth;
                        }
                        classes = 'daiquiri-table-col-' + colId;
                        if (self.colsmodel[i].sortable != 'false') {
                            classes += ' sortable';
                        }
                        html += '<th id="' + self.id + '-thead-col-' + colId + '" style="width:' + width + '" class="' + classes + '">';
                        if (i != 0) {
                            html += '<div class="handle-left pull-left"></div>';
                        }
                        html += '<span>' + self.colsmodel[i].name + '</span>';
                        html += '<div class="handle-right pull-right"></div>';
                        if (self.colsmodel[i].sortable != 'false') {
                            html += '<i id="' + self.id + '-thead-sort" class="icon-chevron-down pull-right"></i>';
                        }
                        html += '</th>';
                    }
                }
                html += '</tr>';
                $('thead',self.container).append(html);

                $('th', self.container).click(function () {
                    var id = $(this).attr('id').match(/(.+)-thead-col-\d+/)[1];
                    var self = daiquiri.table.items[id];
                    
                    // determine which column was clicked
                    var element = $(this)
                    var classes = element.attr('class');
                    var colClass = classes.match(/daiquiri-table-col-\d+/)[0];
                    
                    // remove 'selected' class from other elements and add to this column
                    $('.daiquiri-table-col-selected', 'table', self.container).removeClass('daiquiri-table-col-selected');

                    if (classes.indexOf('daiquiri-table-col-selected') == -1) {
                        // add 'selected' class to column header
                        $('.' + colClass, self.container).addClass('daiquiri-table-col-selected');
                    }
                });
                
                // add sorting function to click on header
                $('i', 'th', self.container).click( function () {
                    var id = $(this).attr('id').match(/(.+)-thead-sort/)[1];
                    var self = daiquiri.table.items[id];

                    // determine which column was clicked
                    var element = $(this);
                    var classes = element.attr('class');
                    var colName = $('span', element.parent()).text();
                            
                    // manipulate arrow and change sort options
                    if (classes.indexOf('sorted') == -1) {
                        $('i.sorted', self.container).removeClass('sorted').removeClass('icon-chevron-up').addClass('icon-chevron-down');
                        element.addClass('sorted');
                        element.addClass('icon-chevron-down');
                        self.params.sort = colName + ' ASC';
                    } else {
                        if (element.attr('class').indexOf('icon-chevron-down') != -1) {
                            element.removeClass('icon-chevron-down').addClass('icon-chevron-up');
                            self.params.sort = colName + ' DESC';
                        } else {
                            element.removeClass('icon-chevron-up').addClass('icon-chevron-down');
                            self.params.sort = colName + ' ASC';
                        }
                    }
                            
                    // display new set of rows
                    self.rows();

                    // return but do not trigger click event in parent th
                    return false;
                });
                
                // make columns resizsable
                // disable click events on handle divs left an right of the header
                $('.handle-right').click(function () {
                    return false;
                });
                $('.handle-left').click(function () {
                    return false;
                });
                
                // on mousedown, init global resizing object
                $('.handle-right').on('mousedown', function (e) {
                    var match = $(this).parent().attr('id').match(/(.+)-thead-col-(\d+)/);
                    var id = match[1];
                    var colId = match[2];
                    var self = daiquiri.table.items[id];
                    var cols = $('th.daiquiri-table-col-' + colId, self.container);
                    
                    daiquiri.table.resizing = {
                        'cols': cols,
                        'zero': e.pageX,
                        'width': $('th.daiquiri-table-col-' + colId, self.container).width()
                    }
                });
                $('.handle-left').on('mousedown', function (e) {
                    var match = $(this).parent().attr('id').match(/(.+)-thead-col-(\d+)/);
                    var id = match[1];
                    var colId = match[2];
                    var self = daiquiri.table.items[id];
                    var cols = $('th.daiquiri-table-col-' + (colId - 1), self.container);

                    daiquiri.table.resizing = {
                        'cols': cols,
                        'zero': e.pageX,
                        'width': $('th.daiquiri-table-col-' + (colId - 1), self.container).width()
                    }
                    return false;
                });
                
                // on mouse up remove resizing object
                $(document).on('mouseup', function () {
                    if (daiquiri.table.resizing != null) {
                        daiquiri.table.resizing = null;
                    }
                });
                
                // on mousemove perform resizing if resizing object is not null
                $(document).on("mousemove", function(e) {
                    if (daiquiri.table.resizing != null) {
                        var delta = e.pageX - daiquiri.table.resizing.zero;

                        var width = daiquiri.table.resizing.width + delta;
                        if (width < 50) {
                            width = 50;
                        }
                        daiquiri.table.resizing.cols.width(width);
                    }
                });
                
                // display new set of rows
                self.rows();
            } else {
                daiquiri.common.jsonError(json);
            }
        }
    });
}

/**
 * Gets the rows by ajax and constructs the td elements. On success it calls an optional function.
 */
daiquiri.table.Table.prototype.rows = function () {
    var self = this;

    // get the rows via ajax
    $.ajax({
        url: self.opt.rowsurl, 
        type: 'GET',
        data: $.extend({}, self.opt.params, self.params),
        dataType: 'json',
        headers: $.extend({}, self.opt.header, {
            'Accept': 'application/json'
        }),
        error: daiquiri.common.ajaxError,
        success: function (json) {
            if (json.status == 'ok') {
                // store information from server in params
                self.pages = json.pages;

                // update pager
                var html = '<p>Page ' + json.page + ' of ' + json.pages;
                /*
                var html = '<p>Page ' + json.page + ' of ' + json.pages + ' (' + json.total + ' ';
                if (json.total == 1) {
                    html += 'row';
                } else {
                    html += 'rows';
                }
                html += ' total)</p>';
                */
                var paging = $('#' + self.id + '-pager-paging');
                paging.children().remove();
                paging.append(html);

                // get the id of the selected column
                var selected = $('.daiquiri-table-col-selected');
                if (selected.length != 0) {
                    var selectedId = selected.attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                }

                // construct html elements for the rows
                html = '';
                var i,j,classes,text,format,rowId,colId,cell,ext;
                for (j = 0; j < json.nrows; j++) {
                    html += '<tr>';

                    // get the id of the row
                    rowId = json.rows[j]["id"];
                    if (typeof rowId === 'undefined') rowId = j;

                    // get the "cell" with the actual data
                    cell = json.rows[j]["cell"];

                    // loop over rows
                    for (i = 0; i < self.ncols; i++) {
                        // get the column
                        col = self.colsmodel[i];

                        if (col.hidden != true) {
                            // get the id of the column
                            colId = col["id"];
                            if (typeof colId === 'undefined') colId = i;

                            // format cell according to colsmodel
                            classes = 'daiquiri-table-col-' + colId + ' daiquiri-table-row-' + rowId;

                            if (typeof col.format === 'undefined') {
                                text = cell[i];
                            } else {
                                if (col.format.type == 'filelink' && cell[i] != null) {
                                    extension = cell[i].match(/(?:\.([^.]+))?$/)[1];

                                    if ($.inArray(extension.toLowerCase(),['jpg','jpeg','png','bmp','txt']) != -1) {
                                        target = 'target="_blank"';
                                    } else {
                                        target = '';
                                    }

                                    classes += ' daiquiri-table-downloadable';
                                    text = '<a ' + target + 'href="' + col.format.base + '?name=' + cell[i] + '">' + cell[i] + '</a>';
                                } else if (col.format.type == 'link') {
                                    text = '<a target="_blank" href="' + cell[i] + '">' + cell[i] + '</a>';
                                } else {
                                    text = cell[i];
                                }
                            }

                            // add the selected class for cells in the selected column
                            if (selectedId != undefined && i == selectedId) {
                                classes += ' daiquiri-table-col-selected';
                            }
                        
                            html += '<td class="' + classes + '">' + text + '</td>';
                        }
                    }
                    html += '</tr>';
                }

                // get rid of the messages
                $('.daiquiri-table-message', this.container).children().remove();

                // get rid of the old rows
                var tbody = $('tbody',self.container)
                tbody.children().remove();

                // append the new rows to the body of the table
                tbody.append(html);
                
                // add click event for the rows (for row selection)
                $('td', self.container).click(function () {
                    var element = $(this)
                    var classes = element.attr('class');
                    var rowClass = classes.match(/daiquiri-table-row-\d+/)[0];

                    if (self.opt.multiselect) {
                        // deselect only THIS row
                        $('.' + rowClass).removeClass('daiquiri-table-row-selected');
                    } else {
                        // deselect all rows
                        $('.daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');
                    }

                    if (classes.indexOf('daiquiri-table-row-selected') == -1) {
                        $('.' + rowClass).addClass('daiquiri-table-row-selected');
                    }
                });

                // call the success function;
                if (self.opt.success != undefined) {
                    self.opt.success(self);
                }
            } else {
                daiquiri.common.jsonError(json);
            }
        }
    });
}