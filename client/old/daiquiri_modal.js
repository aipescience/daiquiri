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

/**
 * Constructor-like function for the modal class. 
 */
daiquiri.Modal = function (opt) {
    this.opt = opt;
    this.show();
};

/**
 * Fetches the content of the model (if needed) and calls the display function.
 */
daiquiri.Modal.prototype.show = function() {
    var self = this;

    // get rid of any old modals
    $('.daiquiri-modal').remove();

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
                // remove main div
                var main = html.indexOf('<div class="main">')
                if (main != -1) {
                    html = html.substring(main + '<div class="main">'.length ,html.lastIndexOf('</div>'));
                }

                // get title
                var match = html.match(/\<h2\>(.*?)\<\/h2\>/g);
                if (match && match.length) {
                    self.title = match[0];

                    // remove title
                    html = html.substring(html.indexOf(self.title) + self.title.length);
                } else {
                    self.title = '';
                }

                self.html = html

                self.display();
            }
        });
    } else if (typeof this.opt.html !== 'undefined') {
        self.title = this.opt.title;
        self.html = this.opt.html;
        self.display();
    }
};

/**
 * Displays the modal.
 */
daiquiri.Modal.prototype.display = function () {
    var self = this;

    // 'lock' body
    $('body').css('overflow','hidden');

    // set title to empty string in nessesary
    if (typeof(this.title) === 'undefined') {
        this.title = ''
    }

    // create diolog
    this.modal = $('<div />',{
        'html': '<div class="daiquiri-modal-dialog"><div class="daiquiri-modal-close-container"><a class="daiquiri-modal-close" href="#">x</a></div><div class="daiquiri-modal-title">' + this.title + '</div><div class="daiquiri-modal-body">' + this.html + '</div></div>',
        'class': 'daiquiri-modal'
    }).appendTo('body');

    // get dialog div
    var dialog = $('.daiquiri-modal-dialog');

    // adjust height and width
    if (typeof this.opt.height !== 'undefined') {
        dialog.height(this.opt.height);
    }
    if (typeof this.opt.width !== 'undefined') {
        dialog.width(this.opt.width);
    } else {
        dialog.width(600);
    }

    // check for max height
    var maxHeight = $(window).height() - 120;
    if (dialog.height() > maxHeight) {
        dialog.height(maxHeight);
        $('.daiquiri-modal-body').height(maxHeight - $('.daiquiri-modal-title').height() - 40);
    }

    // adjust left and top margin
    var leftMargin = ($(window).width() - dialog.width()) / 2 - 10;
    dialog.css('marginLeft', leftMargin);
    var topMargin = ($(window).height() - dialog.height()) / 2 - 10;
    dialog.css('marginTop', topMargin);

    // close button link
    $('.daiquiri-modal-close').click(function() {
        $('.daiquiri-modal').remove();
        $('body').css('overflow','auto');
        return false;
    });

    // prev and next button
    $('.daiquiri-modal-next').click(function() {
        // call success function
        if (typeof self.opt.next !== 'undefined') {
            self.opt.next();
        }
        return false;
    });
    $('.daiquiri-modal-prev').click(function() {
        // call success function
        if (typeof self.opt.prev !== 'undefined') {
            self.opt.prev();
        }
        return false;
    });

    // enable button
    $('.daiquiri-modal button').on('click', function () {
        // call success function
        if (typeof self.opt.success !== 'undefined') {
            self.opt.success();
        }

        // remove modal
        $('.daiquiri-modal').remove();
        $('body').css('overflow','auto');
    });

    // ajaxify form
    $('.daiquiri-modal form input[type=submit]').on('click', function() {
        var name = $(this).attr('name');
        if (name.indexOf('submit', name.length - 6) !== -1) { 
            var form = $('form','.daiquiri-modal');
            var action = form.attr('action');
            var values = form.serialize() + '&submit=' + $(this).attr('value');

            $.ajax({
                url: action,
                type: 'POST',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json'
                },
                data: values,
                error: daiquiri.common.ajaxError,
                success: function (json) {
                    if (json.status == 'ok') {
                        // remove modal
                        $('.daiquiri-modal').remove();
                        $('body').css('overflow','auto');

                        // call success function
                        if (typeof self.opt.success!== 'undefined') {
                            self.opt.success(json, action);
                        }
                    } else if (json.status == 'error') {
                        daiquiri.common.showFormErrors(form, json.errors);
                    } else {
                        daiquiri.common.jsonError(json);
                    }
                }
            });
        } else {
            // cancel was clicked
            $('.daiquiri-modal').remove();
            $('body').css('overflow','auto');
        }

        return false;
    });

    // enable esc key
    $(document).keyup(function(e) {
        if (e.keyCode == 27) {
            // esc pressed
            $('.daiquiri-modal').remove();
            $('body').css('overflow','auto');
            return false;
        }
    });

    // refresh a possible code mirror textarea
    $('.daiquiri-modal .codemirror').daiquiri_codemirror();
};
