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

(function($){
    $.fn.extend({ 
        daiquiri_query_buttons: function(opt) {
            return this.each(function() {
                $("[rel=tooltip]", this).tooltip();

                // Submit value of the toggle button group
                // Solution from stackoverflow/11475069
                var group = $('fieldset.btn-group[data-toggle-name]', this);
                var form = $(this);
                var name = group.attr('data-toggle-name');
                var hidden = $('input[name="' + name + '"]', form);
                $('button', group).each(function () {
                    $(this).click (function () {
                        hidden.val($(this).data("toggle-value"));
                    });
                });

                // activate the default queue button
                if(typeof($('button[name$=_def]', group)).attr('name') == "undefined") {
                    $('button:first-child', group).click();
                } else {
                    $('button[name$=_def]', group).click();
                }
            });
        }
    });
})(jQuery);
