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
                if (typeof daiquiri.codemirror.items[id] === 'undefined') {
                    if ($(this).attr('readonly') != 'readonly') {
                        daiquiri.codemirror.items[id] = new daiquiri.codemirror.CodeMirror($(this));
                    }
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
