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
daiquiri.data = {};

/**
 * Displays the main table of the user management and ajaxifies the option links.
 */ 
daiquiri.data.Data = function (baseUrl) {
    daiquiri.data.item = this;
    self.url = false;
    this.idle = true;
    this.baseUrl = baseUrl;

    $('#database-browser','#data').daiquiri_browser({
        'url': baseUrl + "/data/databases",
        'columns': ['databases','tables','columns'],
        'action': function(opt) {
            daiquiri.data.item.show('database-browser',opt);
        },
    });
    $('#function-browser','#data').daiquiri_browser({
        'url': baseUrl + "/data/functions",
        'columns': ['functions'],
        'action': function(opt) {
            daiquiri.data.item.show('function-browser',opt);
        },
    });
}

daiquiri.data.Data.prototype.show = function (id, opt) {
    var self = daiquiri.data.item;

    // default is to reload old
    var url = self.url;
    if (typeof id !== 'undefined') {
        if (id == 'database-browser') {
            if (typeof opt.left !== 'undefined') {
                if (typeof opt.center === 'undefined' ) {
                     url = self.baseUrl + '/data/databases/show/?db=' + opt.left;
                } else {
                    if (typeof opt.right === 'undefined' ) {
                        url = self.baseUrl + '/data/tables/show/?db=' + opt.left + '&table=' + opt.center;
                    } else {
                        url = self.baseUrl + '/data/columns/show/?db=' + opt.left + '&table=' + opt.center + '&column=' + opt.right;
                    }
                }
            } 
        } else if (id == 'function-browser') {
            url = self.baseUrl + '/data/';
            if (typeof opt.left !== 'undefined') {
                url = self.baseUrl + '/data/functions/show/?function=' + opt.left;
            }
        }
    }

    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: url,
            dataType: 'text',
            headers: {
                'Accept': 'application/html'
            },
            error: daiquiri.common.ajaxError,
            success: function (html){
                $('#display','#data').children().remove();
                $('#display','#data').append(html);
                self.url = url // store url for later
                self.idle = true;

                // bind links to modals
                $('a','#display','#data').click(function(){
                    // remove any other old modal
                    $('.daiquiri-modal').remove();

                    var modal = new daiquiri.Modal({
                        'url': this.href,
                        'width': 725,
                        'class': 'daiquiri-user-table-modal',
                        'success': function () {
                            $('input[type=submit]','form','.daiquiri-modal').click(function () {
                                if ($(this).attr('name') == 'submit') {
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
                                                $('.daiquiri-modal').modal('hide');

                                                // redisplay
                                                daiquiri.data.item.show();
                                            } else if (json.status == 'error') {
                                                daiquiri.common.updateCsrf(form, json.csrf);
                                                daiquiri.common.showFormErrors(form, json.errors);
                                            } else {
                                                daiquiri.common.jsonError(json);
                                            }
                                        }
                                    });
                                } else {
                                    // cancel was clicked
                                    $('.daiquiri-modal').modal('hide');
                                }
                                return false;
                            });
                        }
                    });

                    modal.show();
                    return false;
                });
            }
        });    
    }
}
