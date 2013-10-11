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
    'name': null,
    'url': null,
    'width': null,
    'height': null
};

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

    // set the dimensions of the container
    if (opt.width) {
        this.container.width(opt.width);
    }
    this.width = this.container.width();
    
    if (opt.height) {
        this.container.height(opt.height);
    }
    this.height = this.container.height();

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

    // left column
    var html = '<div class="daiquiri-browser-left pull-left">'
    html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
    html += '<li class="nav-header">Databases</li>';
    html += '</ul>';
    html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
    html += '</ul>';
    html += '</div>';
    
    
    // center column
    html += '<div class="daiquiri-browser-center pull-left">'
    html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
    html += '<li class="nav-header">Tables</li>';
    html += '</ul>';
    html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
    html += '</ul>';
    html += '</div>';
    
    // right column
    html += '<div class="daiquiri-browser-right pull-left">'
    html += '<ul class="daiquiri-browser-head nav nav-pills nav-stacked">';
    html += '<li class="nav-header">Columns</li>';
    html += '</ul>';
    html += '<ul class="daiquiri-browser-body nav nav-pills nav-stacked">';
    html += '</ul>';
    html += '</div>';

    self.container.append(html);
    
    this.resize();

    $.ajax({
        type: 'POST',
        url: self.opt.url,
        dataType: 'json',
        headers: {
            'Accept': 'application/json'
        },
        error: daiquiri.common.ajaxError,
        success: function(json) {
            // check if everything went ok
            if (json.status == 'ok') {
                self.databases = json.data;
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
    var i,j,k,html,cl,li;

    // remove content
    $('.nav-item','.daiquiri-browser-left', self.container).remove();
    $('.nav-item','.daiquiri-browser-center', self.container).remove();
    $('.nav-item','.daiquiri-browser-right', self.container).remove();
    
    // left column (databases)
    if (self.databases != undefined) {
        for (i = 0; i < self.databases.length; i++){
            if (i == iActive) {
                cl = "nav-item active";
            } else {
                cl = "nav-item";
            }

            li = $('<li/>',{
                'class': cl,
                'html': '<a href="#daiquiri-browser-left-' + i + '">' + self.databases[i].name + '</a>'
            }).appendTo($('.daiquiri-browser-body','.daiquiri-browser-left', self.container));
        
            if (i == iActive) {
                $('a',li).click(function() {
                    var iText = $(this).text();
                    self.opt.action('`' + iText + '`');
                    return false;
                });
            } else {
                $('a',li).click(function() {
                    var iNew = $(this).attr('href').split("-").pop();
                    self.displayColumns(iNew,0);
                    return false;
                });
            }
        }
        
        // center column (tables)
        if (self.databases[iActive] != undefined) {
            for (j = 0; j < self.databases[iActive].tables.length; j++){
                if (j == jActive) {
                    cl = 'nav-item active';
                } else {
                    cl = 'nav-item';
                }
            
                li = $('<li/>',{
                    'class': cl,
                    'html': '<a href="#daiquiri-browser-center-' + j + '">' + self.databases[iActive].tables[j].name + '</a>'
                }).appendTo($('.daiquiri-browser-body','.daiquiri-browser-center', self.container));

                if (j == jActive) {
                    $('a',li).click(function() {
                        var iText = $('.active','.daiquiri-browser-left', self.container).text();
                        var jText = $(this).text();
                        self.opt.action('`' + iText + '`.`' + jText + '`');
                        return false;
                    });
                } else {
                    $('a',li).click(function() {
                        var jNew = $(this).attr('href').split("-").pop();
                        self.displayColumns(iActive,jNew);
                        return false;
                    });
                }
            }
    
            // right column (columns)
            if (self.databases[iActive].tables[jActive] != undefined) {
                for (k = 0; k < self.databases[iActive].tables[jActive].columns.length; k++){
                    li = $('<li/>',{
                        'class': 'nav-item',
                        'html': '<a href="#daiquiri-browser-right-' + k + '">' + self.databases[iActive].tables[jActive].columns[k].name + '</a>'
                    }).appendTo($('.daiquiri-browser-body','.daiquiri-browser-right', self.container));
        
                    $('a',li).click(function() {
                        var iText = $('.active','.daiquiri-browser-left', self.container).text();
                        var jText = $('.active','.daiquiri-browser-center', self.container).text();
                        var kText = $(this).text();
                        self.opt.action('`' + iText + '`.`' + jText + '`.`' + kText + '`');
                        return false;
                    })
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
    
    var currentWidth;
    var width = Math.ceil((self.width - 2) / 3);
    // subtract more pixels if zoomed out
    var zoom = window.outerWidth/window.innerWidth;
    if (zoom < 0.99) width -= 1;
    if (zoom < 0.51) width -= 1;
    if (zoom < 0.26) width -= 1;
    var remainingWidth = self.width - 2; /* -2 for 2 borders, apearantly ? */

    $('div', self.container).each(function () {
        if (remainingWidth < width) {
            currentWidth = remainingWidth;
        } else {
            currentWidth = width;
        }
        remainingWidth -= currentWidth;
        $('.daiquiri-browser-body', this).width(currentWidth);

        var height = self.height - $('.daiquiri-browser-head',this).height() - 1;
        $('.daiquiri-browser-body', this).height(height);
    });
};
