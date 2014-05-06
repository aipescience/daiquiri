/*  
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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
daiquiri.browser = {};

/**
 * jquery plugin to insert the browser in a given jquery selection
 */
(function($){
    $.fn.extend({ 
        daiquiri_browser: function(opt) {
            opt = $.extend({},daiquiri.browser.defaults, opt);
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (daiquiri.browser.items[id] == undefined) {
                    daiquiri.browser.items[id] = new daiquiri.browser.Browser($(this),opt);
                } else {
                    daiquiri.browser.items[id].reset(opt);
                }
            });
        }
    });
})(jQuery);

/**
 * Object to hold the different instances of the Browser class.
 */
daiquiri.browser.items = {};

/**
 * Object to hold the default options of the browser.
 */
daiquiri.browser.opt = {
    'action': null,
    'url': null,
    'columns': []
};


/**
 * Switch for double click functionality.
 */
daiquiri.browser.clicked = false;

/**
 * Constructor-like function for the Browser class. 
 */
daiquiri.browser.Browser = function(container, opt) {
    // set state
    this.container = container;
    this.opt = opt;
    this.id = container.attr('id');

    // clear old container
    this.container.children().remove();

    // set class
    this.container.addClass('daiquiri-browser');

    this.displayBrowser();
};

/**
 * Resets the browser to its inital state and overwrites the opt object.
 */
daiquiri.browser.Browser.prototype.reset = function (opt) {
    this.opt = opt;
    this.container.children().remove();
    this.displayBrowser();
};

/**
 * Displays the browser.
 */
daiquiri.browser.Browser.prototype.displayBrowser = function () {
    var self = this;

    var html = ''; 
    // left column
    if (self.opt.columns.length > 0) {
        html += '<div class="daiquiri-browser-left pull-left">'
        html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
        html += '<li class="nav-header">' + self.opt.columns[0] + '</li>';
        html += '</ul>';
        html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
        html += '</ul>';
        html += '</div>';
    }
    if (self.opt.columns.length > 1) {
        // center column
        html += '<div class="daiquiri-browser-center pull-left">'
        html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
        html += '<li class="nav-header">' + self.opt.columns[1] + '</li>';
        html += '</ul>';
        html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
        html += '</ul>';
        html += '</div>';
    }
    if (self.opt.columns.length > 2) {   
        // right column
        html += '<div class="daiquiri-browser-right pull-left">'
        html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
        html += '<li class="nav-header">' + self.opt.columns[2] + '</li>';
        html += '</ul>';
        html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
        html += '</ul>';
        html += '</div>';
    }

    self.container.append(html);
    
    self.resize();

    $.ajax({
        url: self.opt.url,
        dataType: 'json',
        headers: {
            'Accept': 'application/json'
        },
        error: daiquiri.common.ajaxError,
        success: function(json) {
            // check if everything went ok
            if (json.status == 'ok') {
                self.json = json;
                self.displayColumns(0,0);
                
                $(window).bind('resize', function() {
                    setTimeout("daiquiri.browser.items['" + self.id + "'].resize();",100);
                });
            } else {
                daiquiri.common.jsonError(json);
            }
        }
    });
};
 
/**
 * Displays the three columns.
 */
daiquiri.browser.Browser.prototype.displayColumns = function (iActive, jActive) {
    var self = this;
    var i,j,k,html,cl,li,leftData,centerData,rightData;

    // remove content
    $('.daiquiri-browser-left .nav-item', self.container).remove();
    $('.daiquiri-browser-center .nav-item', self.container).remove();
    $('.daiquiri-browser-right .nav-item', self.container).remove();

    // left column (databases)
    leftData = self.json[self.opt.columns[0]];
    if (typeof leftData != 'undefined' && leftData.length > 0) {
        for (i = 0; i < leftData.length; i++){
            if (i == iActive) {
                cl = "nav-item active";
            } else {
                cl = "nav-item";
            }
            li = $('<li/>',{
                'class': cl,
                'html': '<a href="#daiquiri-browser-left-' + i + '">' + leftData[i].name + '</a>'
            }).appendTo($('.daiquiri-browser-left .daiquiri-browser-body', self.container));


            $('a',li).click(function (e) {
                var id = $(e.target).parents('.daiquiri-browser').attr('id');
                daiquiri.browser.clicked = daiquiri.browser.items[id];

                self.displayColumns($(this).attr('href').split("-").pop(),0);

                daiquiri.common.singleDoubleClick(e, function (e) {
                    // single click
                    if (daiquiri.browser.clicked.opt.click) daiquiri.browser.clicked.opt.click({
                        'left': $('.daiquiri-browser-left .active', daiquiri.browser.clicked.container).text(),
                    });
                }, function (e) {
                    // double click
                    if (daiquiri.browser.clicked.opt.dblclick) daiquiri.browser.clicked.opt.dblclick({
                        'left': $('.daiquiri-browser-left .active', daiquiri.browser.clicked.container).text(),
                    });
                }); 

                return false;
            });
        }

        // center column (tables)
        centerData = leftData[iActive][self.opt.columns[1]];
        if (typeof centerData != 'undefined' && centerData.length > 0) {
            for (j = 0; j < centerData.length; j++){
                if (j == jActive) {
                    cl = 'nav-item active';
                } else {
                    cl = 'nav-item';
                }
            
                li = $('<li/>',{
                    'class': cl,
                    'html': '<a href="#daiquiri-browser-center-' + j + '">' + centerData[j].name + '</a>'
                }).appendTo($('.daiquiri-browser-center .daiquiri-browser-body', self.container));

                $('a',li).click(function (e) {
                    var id = $(e.target).parents('.daiquiri-browser').attr('id');
                    daiquiri.browser.clicked = daiquiri.browser.items[id];

                    self.displayColumns(iActive,$(this).attr('href').split("-").pop());

                    daiquiri.common.singleDoubleClick(e, function (e) {
                        // single click
                        if (daiquiri.browser.clicked.opt.click) daiquiri.browser.clicked.opt.click({
                            'left': $('.daiquiri-browser-left .active', daiquiri.browser.clicked.container).text(),
                            'center': $('.daiquiri-browser-center .active', daiquiri.browser.clicked.container).text()
                        });
                    }, function (e) {
                        // double click
                        if (daiquiri.browser.clicked.opt.dblclick) daiquiri.browser.clicked.opt.dblclick({
                            'left': $('.daiquiri-browser-left .active', daiquiri.browser.clickedcontainer).text(),
                            'center': $('.daiquiri-browser-center .active', daiquiri.browser.clicked.container).text()
                        });
                    }); 

                    return false;
                });
            }
    
            // right column (columns)
            rightData = centerData[jActive][self.opt.columns[2]];
            if (typeof rightData != 'undefined' && rightData.length > 0) {
                for (k = 0; k < rightData.length; k++){
                    li = $('<li/>',{
                        'class': 'nav-item',
                        'html': '<a href="#daiquiri-browser-right-' + k + '">' + rightData[k].name + '</a>'
                    }).appendTo($('.daiquiri-browser-right .daiquiri-browser-body', self.container));
        
                    $('a',li).click(function (e) {
                        var id = $(e.target).parents('.daiquiri-browser').attr('id');
                        daiquiri.browser.clicked = daiquiri.browser.items[id];

                        $('.daiquiri-browser-right .active').removeClass('active');
                        $(this).parent().addClass('active');

                        daiquiri.common.singleDoubleClick(e, function (e) {
                            // single click
                            if (daiquiri.browser.clicked.opt.click) daiquiri.browser.clicked.opt.click({
                                'left': $('.daiquiri-browser-left .active', daiquiri.browser.clicked.container).text(),
                                'center': $('.daiquiri-browser-center .active', daiquiri.browser.clicked.container).text(),
                                'right': $('.daiquiri-browser-right .active', daiquiri.browser.clicked.container).text()
                            });
                        }, function (e) {
                            // double click
                            if (daiquiri.browser.clicked.opt.dblclick) daiquiri.browser.clicked.opt.dblclick({
                                'left': $('.daiquiri-browser-left .active', daiquiri.browser.clicked.container).text(),
                                'center': $('.daiquiri-browser-center .active', daiquiri.browser.clicked.container).text(),
                                'right': $('.daiquiri-browser-right .active', daiquiri.browser.clicked.container).text()
                            });
                        }); 

                        return false;
                    });
                }
            }
        }
    }
};
    
/**
 * Resizes the browser to fit into container
 */
daiquiri.browser.Browser.prototype.resize = function() {
    var self = this;
    var width = self.container.width();
    var height = self.container.height();
    var zoom = window.outerWidth / window.innerWidth;

    var currWidth,currHeight;
    var remainingWidth = width - 2;
    if (zoom < 0.99) remainingWidth -= 1;
    if (zoom < 0.68) remainingWidth -= 2;
    if (zoom < 0.34) remainingWidth -= 2;
    if (zoom < 0.26) remainingWidth -= 2;

    var partWidth = Math.floor(width / self.opt.columns.length);

    // left part
    currWidth = partWidth;
    currHeight = height - $('.daiquiri-browser-head','.daiquiri-browser-left', self.container).height() - 1;
    $('.daiquiri-browser-left', self.container).width(currWidth);
    $('.daiquiri-browser-body','.daiquiri-browser-left', self.container).height(currHeight);
    remainingWidth -= currWidth;

    // center part
    currWidth = partWidth;
    currHeight = height - $('.daiquiri-browser-head','.daiquiri-browser-center', self.container).height() - 1;
    $('.daiquiri-browser-center', self.container).width(currWidth);
    $('.daiquiri-browser-body','.daiquiri-browser-center', self.container).height(currHeight);
    remainingWidth -= currWidth;

    // right part
    currWidth = remainingWidth;
    currHeight = height - $('.daiquiri-browser-head','.daiquiri-browser-right', self.container).height() - 1;
    $('.daiquiri-browser-right', self.container).width(currWidth);
    $('.daiquiri-browser-body','.daiquiri-browser-right', self.container).height(currHeight);
};
