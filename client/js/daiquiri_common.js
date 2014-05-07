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
daiquiri.common = {};

/**
 * Starts the polling by calling the emit poll function for the first time.
 */
daiquiri.common.startPoll = function(timeout) {
    daiquiri.common.poll = daiquiri.common.emitPoll(timeout);
}

/**
 * Emits the 'poll' signal periodically, specified by timeout.
 */
daiquiri.common.emitPoll = function(timeout) {
    daiquiri.common.poll = setTimeout('daiquiri.common.emitPoll('+ timeout +');', timeout);
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
    if (typeof daiquiri.common.poll != 'undefined') {
        clearTimeout(daiquiri.common.poll);
    }
        
    console.log('Ajax error: ' + jqXHR.statusText + ' (' + jqXHR.status + ')');

    if (jqXHR.status == 403) {
        new daiquiri.Modal({
            'html': '<h2>Session expired</h2><p>Your session is expired. Please log in again.</p><p><button class="btn btn-primary">Login</button></p>',
            'success': function () {
                window.location.replace(window.location.pathname);
            }
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

daiquiri.common.clicked = false;

daiquiri.common.singleDoubleClick = function(e, singleClk, doubleClk) {
    if (daiquiri.common.clicked == true) {
        clearTimeout(daiquiri.common.timeout);
        doubleClk(e);
        daiquiri.common.clicked = false;
    } else {
        daiquiri.common.clicked = true;
        daiquiri.common.timeout = setTimeout(function() {
            daiquiri.common.clicked = false;
            singleClk(e);
        }, 300);
    }
};