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
daiquiri.common = {};

/**
 * Emits the 'poll' signal periodically, specified by timeout.
 */
daiquiri.common.poll = function(timeout) {
    setTimeout('daiquiri.common.poll('+ timeout +');', timeout);
    $(window).trigger('poll');
}

/**
 * Displays the form errors next to the corresponding fields
 */
daiquiri.common.showFormErrors = function(form, errors) {
    // remove old errors
    $('.daiquiri-form-error').remove();
    
    // loop over new errors and append after corresponding fields
    $.each(errors, function(field, errors) {
        var e;
        var classes = 'daiquiri-form-error unstyled text-error';
        if (field == 'form' || /^.*csrf$/.test(field)) {
            e = $(form);
        } else {
            e = $('#' + field);
            if (e.parents('fieldset').hasClass('daiquiri-form-horizontal-group')) {
                classes += ' help-inline';
            }
        }

        var html = '<ul class="' + classes +'">';
        $.each(errors, function (key, value) {
            html += '<li>' + value + '</li>';
        });
        html += '</ul>';
        e.after(html);
    });
}

/**
 * Updated the csrf token of a given form
 */
daiquiri.common.updateCsrf = function(form, csrf) {
    if (csrf != undefined) {
        $('.daiquiri-csrf', $(form)).attr('value', csrf);
    } else {
        console.log('Error: No new csrf was provided!');
    }
}

/** 
 * Displays errors with ajax calls.
 */
daiquiri.common.ajaxError = function(jqXHR, textStatus, errorThrown) {
    if (jqXHR.status == 403 || jqXHR.status == 503) {
        var location = window.location.href.split('#')[0]
        window.location.replace(location);
    } else {
        //alert('Error with ajax request (Status: ' + jqXHR.status + ').');
        console.log({
            'status': jqXHR.status,
            'statusText': jqXHR.statusText,
            'responseText': jqXHR.responseText
        });
    }
};

/**
 * Displays other ajax related errors (when json.status != 'ok').
 */
daiquiri.common.jsonError = function(json) {
    //alert('Error with ajax request. (Status: ' + json.status + ').');
    console.log(json);
};
