var _daiquiri_table = {
    defaults: {
        'header' : {},
        'nrows': 20,
        'nrowsList': [5,20,100],
        'sort': null,
        'colswidth': '100px'
    },
    items: {}
};

function Daiquiri_Table(container, opt) {
    this.container = container;
    this.id = container.attr('id');
    this.opt = opt;
    this.params = null;
    this.total = null;

    this.init = function () {
        var self = this;

        // create pane for table
        $('<div/>',{
            'class': 'daiquiri-table-pane',
            'html': '<table class="table"><thead></thead><tbody></tbody></table>'
        }).appendTo(this.container);

        // create pager
        $('<div/>',{
            'class': 'daiquiri-table-pager'
        }).appendTo(this.container);
        
        // create pager
        $('<div/>',{
            'class': 'daiquiri-table-message'
        }).appendTo(this.container);

        // set class
        this.container.addClass('daiquiri-table');

        // initial params
        self.params = {
            'nrows': self.opt.nrows,
            'page': 1,
            'sort': self.opt.sort,
            'search': null
        };

        // display table
        this.pager();
        this.cols();
    }

    this.error = function(jqXHR, textStatus, errorThrown) {
        
        $('tbody', this.container).children().remove();
        $('.daiquiri-table-message', this.container).children().remove();
        $('.daiquiri-table-message', this.container).append('<p class="text-error">Table is empty</p>');
    }

    this.pager = function () {
        var self = this;

        var searchHtml = '<input placeholder="Search" type="text" class="span2" />';
        searchHtml += '<button class="btn"><i class="icon-search"></i></button>';
        var search = $('<form/>',{
            'id': self.id + '-pager-search',
            'class': 'daiquiri-table-pager-search-form input-append pull-left',
            'html': searchHtml
        }).submit(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-search/)[1];
            var self = _daiquiri_table.items[id];
            self.params.search = $('input',this).val()
            self.rows();
            return false;
        }).appendTo($('.daiquiri-table-pager', self.container));

        $('#' + self.id + '-pager-search').click(function () {
            $(this).parent().submit();
        });

        $('<button/>',{
            'id': self.id + '-pager-first',
            'class': 'btn daiquiri-table-pager-button daiquiri-table-pager-button-small',
            'html': '<i class="icon-fast-backward"></i>'
        }).click(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-first/)[1];
            var self = _daiquiri_table.items[id];
            self.params.page = 1;
            self.rows();
        }).appendTo($('.daiquiri-table-pager', self.container));

        $('<button/>',{
            'id': self.id + '-pager-prev',
            'class': 'btn daiquiri-table-pager-button daiquiri-table-pager-button-small',
            'html': '<i class="icon-backward"></i>'
        }).click(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-prev/)[1];
            var self = _daiquiri_table.items[id];
            self.params.page -= 1; 
            if (self.params.page < 1) {
                self.params.page = 1;
            }
            self.rows();
        }).appendTo($('.daiquiri-table-pager', self.container));

        $('<button/>',{
            'id': self.id + '-pager-next',
            'class': 'btn daiquiri-table-pager-button daiquiri-table-pager-button-small',
            'html': '<i class="icon-forward"></i>'
        }).click(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-next/)[1];
            var self = _daiquiri_table.items[id];
            self.params.page += 1;
            if (self.params.page > self.total) {
                self.params.page = self.total;
            }
            self.rows();
        }).appendTo($('.daiquiri-table-pager', self.container));
        
        $('<button/>',{
            'id': self.id + '-pager-last',
            'class': 'btn daiquiri-table-pager-button daiquiri-table-pager-button-small',
            'html': '<i class="icon-fast-forward"></i>'
        }).click(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-last/)[1];
            var self = _daiquiri_table.items[id];
            self.params.page = self.total;
            self.rows();
        }).appendTo($('.daiquiri-table-pager', self.container));

        $('<button/>',{
            'id': self.id + '-pager-reset',
            'class': 'btn daiquiri-table-pager-button',
            'html': 'Reset'
        }).click(function () {
            var id = $(this).attr('id').match(/(\w+)-pager-reset/)[1];
            var self = _daiquiri_table.items[id];
            
            $('input','.daiquiri-table-pager-search-form', self.container).val('');
                
            self.params.search = null
            self.params.sort = null
            self.params.page = 1
            self.rows();
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
            var id = $(this).attr('id').match(/(\w+)-pager-nrows/)[1];
            var self = _daiquiri_table.items[id];
            self.params.page = 1;
            self.params.nrows = $(this).val();
            self.rows();
        });
    }

    this.cols = function () {
        var self = this;

        // get the cols via ajax
        $.ajax({
            url: opt.colsurl, 
            type: 'GET',
            data: $.extend({}, self.opt.params, self.params),
            dataType: 'json',
            headers: $.extend({}, self.opt.header, {
                'Accept': 'application/json'
            }),
            error: self.error,
            success: function (json) {
                if (json.status == 'ok') {
                    self.ncols = json.cols.length;
                    self.colsmodel = json.cols;

                    // contruct and append html elements for column headers
                    var html = '<tr>';
                    var width;
                    for (var i = 0; i < self.colsmodel.length; i++) {
                        if (self.colsmodel[i].width != undefined) {
                            width = self.colsmodel[i].width;
                        } else {
                            width = self.opt.colswidth;
                        }
                        html += '<th id="' + self.id + '-thead-col-' + i + '" style="width:' + width + '" class="daiquiri-table-col-' + i + '">';
                        html += self.colsmodel[i].name + '</th>';
                    };
                    html += '</tr>';
                    $('thead',self.container).append(html);

                    // add click event for column headers
                    $('th', self.container).click(function () {
                        var id = $(this).attr('id').match(/(\w+)-thead-col-\d+/)[1];
                        var self = _daiquiri_table.items[id];

                        // determine which column was clicked
                        var element = $(this)
                        var classes = element.attr('class');
                        var colClass = classes.match(/daiquiri-table-col-\d+/)[0];

                        // remove 'selected' class from other elements and add to this column
                        $('.daiquiri-table-col-selected', 'table', self.container).removeClass('daiquiri-table-col-selected');

                        // remove sorting arrow from other column header (for column selection)
                        $('i','th','.daiquiri-table-col-selected', 'table', self.container).remove();

                        if (classes.indexOf('daiquiri-table-col-selected') == -1) {
                            // add 'selected' class to column header
                            $('.' + colClass, self.container).addClass('daiquiri-table-col-selected');

                            // add sorting arrow to column header
                            $('<i/>',{
                                'id': self.id + '-thead-sort',
                                'class': 'icon-arrow-down pull-right'
                            }).click( function () {
                                var id = $(this).attr('id').match(/(\w+)-thead-sort/)[1];
                                var self = _daiquiri_table.items[id];

                                // determine which column was clicked
                                var element = $(this);
                                var colId = element.parent().attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                                var colName = self.colsmodel[colId].name;

                                // exchange icons from 'icon-arrow-down' to 'icon-arrow-up' or vice versa
                                if (element.attr('class').indexOf('icon-arrow-down') != -1) {
                                    element.removeClass('icon-arrow-down').addClass('icon-arrow-up');
                                    self.params.sort = colName + ' ASC';
                                } else {
                                    element.removeClass('icon-arrow-up').addClass('icon-arrow-down');
                                    self.params.sort = colName + ' DESC';
                                }

                                // display new set of rows
                                self.rows();

                                // return but do not trigger click event in parent th
                                return false;
                            }).appendTo(element);
                        }
                    });

                    // display new set of rows
                    self.rows();
                } else {
                    self.error(null,null,json.error)
                }
            }
        });
    }

    this.rows = function () {
        var self = this;

        // get the rows via ajax
        $.ajax({
            url: opt.rowsurl, 
            type: 'GET',
            data: $.extend({}, self.opt.params, self.params),
            dataType: 'json',
            headers: $.extend({}, self.opt.header, {
                'Accept': 'application/json'
            }),
            error: self.error,
            success: function (json) {
                if (json.status == 'ok') {
                    // store information from server in params
                    self.total = json.total;

                    // get the id of the selected column
                    var selected = $('.daiquiri-table-col-selected');
                    if (selected.length != 0) {
                        var selectedId = selected.attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                    }

                    // contrsuct html elements for the rows
                    var html = '';
                    var i,j;
                    for (i = 0; i < json.nrows; i++) {
                        html += '<tr>';
                        for (j = 0; j < self.ncols; j++) {
                            var classes = 'daiquiri-table-col-' + j + ' daiquiri-table-row-' + i;

                            // add the selected class for cells in the selected column
                            if (selectedId != undefined && j == selectedId) {
                                classes += ' daiquiri-table-col-selected';
                            }

                            html += '<td class="' + classes + '">' + json.rows[i][j] + '</td>';
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

                        $('.daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');

                        if (classes.indexOf('daiquiri-table-row-selected') == -1) {
                            $('.' + rowClass).addClass('daiquiri-table-row-selected');
                        }
                    });

                } else {
                    $('tbody',self.container).children().remove();
                }
            }
        });
    }
}

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
                    _daiquiri_table.items[id].init()
                } else {
                    _daiquiri_table.items[id].reset();
                    _daiquiri_table.items[id].opt = opt;
                }
            });
        }
    });
})(jQuery);