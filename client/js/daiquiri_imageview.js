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
    'width': 800,
    'height': 580
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

    //$('#' + this.tableId + ' .daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');
    //$('#' + this.tableId + ' .daiquiri-table-row-' + this.row).addClass('daiquiri-table-row-selected');

    // look for the images properties
    var img = new Image();
    img.onload = function() {
        var width = this.width;
        var height = this.height;

        if (width > daiquiri.imageview.item.opt.width) {
            height = height * daiquiri.imageview.item.opt.width / width;
            width = daiquiri.imageview.item.opt.width;
        }
        if (height > daiquiri.imageview.item.opt.height) {
            width = width * daiquiri.imageview.item.opt.height / height;
            height = daiquiri.imageview.item.opt.height;
        }

        // create modal
        this.modal = new daiquiri.Modal({
            'html': '<div class="daiquiri-imageview-img"><img style="width:' + width + 'px; height:' + height + 'px;" src="' + url + '"></img></div><div class="daiquiri-imageview-navigation"><div class="pagination pull-left text-left"><ul><li><a class="daiquiri-modal-prev" href="#">Previous</a></li></ul></div><div class="pagination pull-right text-right"><ul><li><a class="daiquiri-modal-next" href="#">Next</a></li></ul></div><div class="daiquiri-imageview-title"><a href="' + url + '" target="_blank">' + title + '</a></div></div>',
            'width': width,
            'height': height + 60,
            'next': function () {
                if (daiquiri.imageview.item.idle) {
                    daiquiri.imageview.item.idle = false;
                    daiquiri.imageview.item.next();
                }
            },
            'prev': function () {
                if (daiquiri.imageview.item.idle) {
                    daiquiri.imageview.item.idle = false;
                    daiquiri.imageview.item.prev();
                }
            }
        });
    }
    img.src = url;
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
    $('.daiquiri-imageview-img img').attr('src', url);
    $('.daiquiri-imageview-title a').remove();
    $('<a />', {html: title, href: url, target: '_blank'}).appendTo($('.daiquiri-imageview-title'));
    $('#' + this.tableId + ' .daiquiri-table-row-selected').removeClass('daiquiri-table-row-selected');
    $('#' + this.tableId + ' .daiquiri-table-row-' + this.row).addClass('daiquiri-table-row-selected');
}