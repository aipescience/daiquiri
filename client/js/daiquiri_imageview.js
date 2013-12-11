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

// daiquiri namespace
var daiquiri = daiquiri || {};
daiquiri.imageview = {};

/**
 * Object to hold the ImageView instance.
 */
daiquiri.imageview.item = null;

/**
 * jquery plugin to bind the imageviewer as click event to a given set of dom elements
 */
(function($){
    $.fn.extend({ 
        daiquiri_imageview: function(opt) {
            opt = $.extend({},daiquiri.browser.defaults, opt);
            return this.each(function() {
                var tableId = $(this).attr('id');
                $('.daiquiri-table-image a', this).on('click', function () {
                    daiquiri.imageview.item = new daiquiri.imageview.ImageView($(this),tableId,opt);
                    return false;
                });
            });
        }
    });
})(jQuery);

/**
 * Object to hold the default options.
 */
daiquiri.imageview.opt = {
    'width': 600,
    'height': 600
};

/**
 * Constructor-like function for the ImageView class. 
 */
daiquiri.imageview.ImageView = function (a, tableId, opt) {
    var self = this;

    this.idle = true;

    this.a = a;
    this.tableId = tableId;
    this.opt = $.extend({}, daiquiri.imageview.opt, opt);

    var url = a.attr('href');
    var title = a.text();
    var tablecell = a.parent().attr('class');

    this.col = parseInt(tablecell.match(/daiquiri-table-col-(\d+)/)[1]);
    this.row = parseInt(tablecell.match(/daiquiri-table-row-(\d+)/)[1]);

    $('#' + this.tableId + ' .daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');
    $('#' + this.tableId + ' .daiquiri-table-row-' + this.row).addClass('daiquiri-table-row-selected');

    // create modal
    this.modal = new daiquiri.Modal({
        'html': '<div id="daiquiri-imageview-img" style="width: ' + this.opt.width + 'px; height: ' + this.opt.height + 'px;"><img src="' + url + '"></img></div><div class="daiquiri-imageview-navigation"><div class="pull-left"><button class="btn" id="daiquiri-imageview-prev">Previous Image</button></div><div class="pull-right"><button class="btn" id="daiquiri-imageview-next">Next Image</button></div><div id="daiquiri-imageview-title"><span>' + title + '</span></div></div>',
        'width': this.opt.width + 20,
        'height': this.opt.height + 60,
        'success': function (button) {
            if (daiquiri.imageview.item.idle) {
                daiquiri.imageview.item.idle = false;
                if (button.attr('id') === 'daiquiri-imageview-prev') {
                    daiquiri.imageview.item.prev();
                } else {
                    daiquiri.imageview.item.next();
                }
            }
        },
        'persitent' : true
    });
};

/**
 * Get the next image. 
 */
daiquiri.imageview.ImageView.prototype.next = function () {
    this.a = $('.daiquiri-table-col-' + this.col + '.daiquiri-table-row-' + (this.row + 1) + ' a');
    if (this.a.length === 0) {
        // switch table to next page
        daiquiri.table.items[this.tableId].next(function() {
            var self = daiquiri.imageview.item;
            self.a = $('.daiquiri-table-col-' + self.col + '.daiquiri-table-row-' + (self.row + 1) + ' a');
            if (self.a.length !== 0) {
                self.row += 1;
                self.update();
            }
            self.idle = true;
        });
    } else {
        this.row += 1;
        this.update();
        this.idle = true;
    }
}

/**
 * Get the previous image. 
 */
daiquiri.imageview.ImageView.prototype.prev = function () {
    this.a = $('.daiquiri-table-col-' + this.col + '.daiquiri-table-row-' + (this.row - 1) + ' a');
    if (this.a.length === 0) {
        // switch table to next page
        daiquiri.table.items[this.tableId].prev(function() {
            var self = daiquiri.imageview.item;
            self.a = $('.daiquiri-table-col-' + self.col + '.daiquiri-table-row-' + (self.row - 1) + ' a');
            if (self.a.length !== 0) {
                self.row -= 1;
                self.update();
            }
            self.idle = true;
        });
    } else {
        this.row -= 1;
        this.update();
        this.idle = true;
    }
}

/**
 * Updates the image. 
 */
daiquiri.imageview.ImageView.prototype.update = function () {
    var url = this.a.attr('href');
    var title = this.a.text();
    $('#daiquiri-imageview-img img').attr('src', url);
    $('#daiquiri-imageview-title span').remove();
    $('<span />', {html: title}).appendTo($('#daiquiri-imageview-title'));
    $('#' + this.tableId + ' .daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');
    $('#' + this.tableId + ' .daiquiri-table-row-' + this.row).addClass('daiquiri-table-row-selected');
}