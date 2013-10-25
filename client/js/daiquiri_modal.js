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

/**
 * Constructor-like function for the Table class. 
 */
daiquiri.Modal = function (opt) {
    this.opt = opt;
};

/**
 * Fetches the content of the model (if needed) and calls the display function.
 */
daiquiri.Modal.prototype.show = function() {
    var self = this;
    this.html = '';
    this.padding = '0px';

    // get rid of any old modals
    $('#daiquiri-modal').remove();

    if (typeof this.opt.url !== 'undefined') {
        // get the body by ajax
        $.ajax({
            url: this.opt.url,
            type: 'GET',
            dataType: 'text',
            headers: {
                'Accept': 'application/html'
            },
            error: daiquiri.common.ajaxError,
            success: function (html) {
                self.html = html;
                self.padding = '20px';
                self.display();
            }
        });
    } else {
        if (typeof this.opt.label !== 'undefined') {
            this.html += '<div class="modal-header">';
            this.html += '<h3 id="daiquiri-modal-label">' + this.opt.label + '</h3>';
            this.html += '</div>';
        }

        if (typeof this.opt.body !== 'undefined') {
            this.html += '<div class="modal-body">' + this.opt.body + '</div>';
        }

        if (typeof this.opt.primary !== 'undefined' || typeof this.opt.danger !== 'undefined' || typeof this.opt.button !== 'undefined') {
            this.html += '<div class="modal-footer">';
            if (typeof this.opt.primary !== 'undefined') {
                this.html += '<button id="daiquiri-modal-primary" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">' + this.opt.primary + '</button>';
            }
            if (typeof this.opt.danger !== 'undefined') {
                this.html += '<button id="daiquiri-modal-danger" class="btn btn-danger" data-dismiss="modal" aria-hidden="true">' + this.opt.danger + '</button>';
            } 
            if (typeof this.opt.button !== 'undefined') {
                this.html += '<button id="daiquiri-modal-button" class="btn" data-dismiss="modal" aria-hidden="true">' + this.opt.button + '</button>';
            }
            this.html += '</div>';
        }

        self.display();
    }
};

/**
 * Displays the modal.
 */
daiquiri.Modal.prototype.display = function () {
    var classes = 'modal hide fade daiquiri-modal ';
    if (typeof this.opt.class !== 'undefined') {
        classes += this.opt.class;
    } 
    this.dialog = $('<p/>',{
        'aria-hidden': 'true',
        'aria-labelledby': 'daiquiri-modal-label',
        'class' : classes,
        'id': 'daiquiri-modal',
        'role': 'dialog',
        'tabindex': '-1',
        'html': this.html
    });
    this.dialog.appendTo('body');
    this.dialog.css('padding', this.padding);

    if (typeof this.opt.width !== 'undefined') {
        this.dialog.css('width', this.opt.width + 'px');
        // left margin must be half of the width, minus scrollbar on the left (30px)
        var margin = - ((this.opt.width /2) + 30);
        this.dialog.css('marginLeft', margin + 'px');
    }
    if (typeof this.opt.success!== 'undefined') {
        this.opt.success();
    }

    // refresh a possible code mirror textarea
    this.dialog.on('shown', function () {
        $('textarea', this.dialog).daiquiri_codemirror_refresh();
    });

    this.dialog.modal();
};

/**
 * Hides the modal.
 */
daiquiri.Modal.prototype.hide = function() {
    this.dialog.modal('hide');
};
