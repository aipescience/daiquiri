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
daiquiri.codemirror = {};

/**
 * jquery plugin to insert a code mirror in a given jquery selection
 */
(function($){
    $.fn.extend({
        /**
         * Insert the code mirror and create the corresponding class.
         */ 
        daiquiri_codemirror: function() {
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (typeof daiquiri.codemirror.items[id] !== 'undefined') {
                    delete daiquiri.codemirror.items[id];
                }

                if ($(this).attr('readonly') != 'readonly') {
                    daiquiri.codemirror.items[id] = new daiquiri.codemirror.CodeMirror($(this));
                }
            });
        },
        /**
         * Calls the save method of the underlying code mirror object.
         */
        daiquiri_codemirror_save: function() {
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (typeof daiquiri.codemirror.items[id] !== 'undefined') {
                    daiquiri.codemirror.items[id].cm.save();
                }
            });
        },
        /**
         * Calls the refresh method of the underlying code mirror object.
         */
        daiquiri_codemirror_refresh: function() {
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (typeof daiquiri.codemirror.items[id] !== 'undefined') {
                    daiquiri.codemirror.items[id].cm.refresh();
                }
            });
        },
        /**
         * Inserts the provides string at the position of the caret.
         */
        daiquiri_codemirror_insertAtCaret: function(string) {
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (typeof daiquiri.codemirror.items[id] !== 'undefined') {
                    daiquiri.codemirror.items[id].insertAtCaret(string);
                }
            });
        },
        /**
         * Clears the code mirrors textarea.
         */
        daiquiri_codemirror_clear: function() {
            return this.each(function() {
                var id = $(this).attr('id');
                // check if table is already set
                if (typeof daiquiri.codemirror.items[id] !== 'undefined') {
                    daiquiri.codemirror.items[id].cm.setValue('');
                }
            });
        },
    });
})(jQuery);

/**
 * Object to hold the CodeMirror instances.
 */
daiquiri.codemirror.items = {};

/**
 * Constructor-like function for the Query class. 
 */
daiquiri.codemirror.CodeMirror = function(container, opt) {
    this.container = container;
    this.id = container.attr('id');

    this.cm = CodeMirror.fromTextArea(container.get(0), {
        mode: 'text/x-mysql',
        indentWithTabs: false,
        smartIndent: true,
        matchBrackets : true,
        lineNumbers: true,
        lineWrapping: true,
        autofocus: true
    });

    // adjust with of input field, since span9 does not work with CodeMirror
    this.cm.setSize(container.css('width'),null);
};

/**
 * Inserts the provides string at the position of the caret. 
 */
daiquiri.codemirror.CodeMirror.prototype.insertAtCaret = function(string) {
    var pos = this.cm.getCursor();
    pos['ch'] += string.length;
    this.cm.replaceSelection(string);
    this.cm.setCursor(pos);
    this.cm.focus();
}
