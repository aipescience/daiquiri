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

/*
 * daiquiri_query_buttons - a plugin fo jquery and bootstap
 * 
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

                //activate the default queue button
                if(typeof($('button[name$=_def]', group)).attr('name') == "undefined") {
                    $('button:first-child', group).click();
                } else {
                    $('button[name$=_def]', group).click();
                }
            });
        }
    });
})(jQuery);
