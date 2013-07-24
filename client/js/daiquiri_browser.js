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

var _daiquiri_browser = {
    defaults: {
        'name': null,
        'url': null,
        'width': null,
        'height': null
    },
    items: {}
}

function Daiquiri_Browser(container, opt) {
    // set state
    this.container = container;
    this.opt = opt;

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
    
    /*
     * Displays the browser
     */
    
    this.displayBrowser = function () {
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
        this.displayColumns(0,0);
    }
     
    this.displayColumns = function (iActive, jActive) {
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
    }
    
    /**
     * Resizes the browser to fit into container
     */
    this.resize = function() {
        var self = this;
        
        var currentWidth;
        var width = Math.ceil((self.width - 2) / 3);

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
     
    /**
     * Resets the container to the inital state
     */
    this.reset = function() {
        this.container.children().remove();
    };
}

(function($){
    $.fn.extend({ 
        daiquiri_browser: function(opt) {
            // apply default options
            opt = $.extend(_daiquiri_table.defaults, opt);
                
            return this.each(function() {
                var id = $(this).attr('id');

                // check if table is already set
                if (_daiquiri_browser.items[id] == undefined) {
                    _daiquiri_browser.items[id] = new Daiquiri_Browser($(this),opt);
                } else {
                    _daiquiri_browser.items[id].reset();
                    _daiquiri_browser.items[id].opt = opt;
                }
                
                $.ajax({
                    type: 'POST',
                    url: opt.url,
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json'
                    },
                    error: daiquiri_ajaxError,
                    success: function(json) {
                        // check if everything went ok
                        if (json.status != 'ok') {
                            daiquiri_jsonError(json);
                        } else {
                            _daiquiri_browser.items[id].databases = json.data;
                            _daiquiri_browser.items[id].displayBrowser();
                            
                            $(window).bind('resize', function() {
                                setTimeout("_daiquiri_browser.items['"+id+"'].resize();",100);
                            });
                        }
                    }
                });
            });
        }
    });
})(jQuery);
