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
 * Displays the main table of the user management and ajaxifies the option links.
 */ 
daiquiri.UserTable = function (baseurl) {
    // create the user table inside the div
    $('#table').daiquiri_table({
        'rowsurl': baseurl + '/auth/user/rows',
        'colsurl': baseurl + '/auth/user/cols',
        'params': {
            'options': true
        },
        'success': function() {
            $('a','#table').click(function(){
                // remove any other old modal
                $('.daiquiri-modal').remove();

                var modal = new daiquiri.Modal({
                    'url': this.href,
                    'width': 700,
                    'class': 'daiquiri-user-table-modal',
                    'success': function () {
                        $('.daiquiri-user-back').click(function() {
                            $('.daiquiri-modal').modal('hide');
                            return false;
                        });

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
                                        // reload table and remove modal
                                        daiquiri.table.items['table'].rows();
                                        $('.daiquiri-modal').modal('hide');

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
