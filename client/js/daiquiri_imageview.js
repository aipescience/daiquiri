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
                $(this).on('click', function () {
                    daiquiri.imageview.item = new daiquiri.imageview.ImageView($(this),opt);
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
    'height': 640
};

/**
 * Constructor-like function for the ImageView class. 
 */
daiquiri.imageview.ImageView = function (link, opt) {
    var self = this;

    this.url = link.attr('href');
    this.opt = $.extend({}, daiquiri.imageview.opt, opt);

    // create modal
    this.modal = new daiquiri.Modal({
        'html': '<div><img src="' + this.url + '"></img></div><div class="daiquiri-imageview-navigation"><div class="pull-left"><button class="btn" id="daiquiri-imageview-prev">Previous Image</button></div><div class="pull-right"><button class="btn" id="daiquiri-imageview-next">Next Image</button></div></div>',
        'width': this.opt.width,
        'height': this.opt.height,
        'success': function (button) {
            if (button.attr('id') === 'daiquiri-imageview-prev') {
                daiquiri.imageview.item.prev();
            } else {
                daiquiri.imageview.item.next();
            }
        },
        'persitent' : true
    });
};

/**
 * Get the next image. 
 */
daiquiri.imageview.ImageView.prototype.next = function () {
    console.log('next');
}

/**
 * Get the previous image. 
 */
daiquiri.imageview.ImageView.prototype.prev = function () {
    console.log('prev');
}