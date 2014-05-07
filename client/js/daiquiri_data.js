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
daiquiri.data = {};

/**
 * Displays the main table of the user management and ajaxifies the option links.
 */ 
daiquiri.data.Data = function (baseUrl) {
    daiquiri.data.item = this;
    self.url = false;
    this.idle = true;
    this.baseUrl = baseUrl;

    $('.daiquiri-modal-open').click(function(){
        new daiquiri.Modal({
            'url': this.href,
            'width': 725,
            'class': 'daiquiri-user-table-modal',
            'success': daiquiri.data.item.show
        });
        return false;
    });

    $('#database-browser','#data').daiquiri_browser({
        'url': baseUrl + "/data/databases",
        'columns': ['databases','tables','columns'],
        'click': function(opt) {
            daiquiri.data.item.show('database-browser',opt);
        },
        'dblclick': function(opt) {
            daiquiri.data.item.show('database-browser',opt);
        },
    });
    $('#function-browser','#data').daiquiri_browser({
        'url': baseUrl + "/data/functions",
        'columns': ['functions'],
        'click': function(opt) {
            daiquiri.data.item.show('function-browser',opt);
        },
        'dblclick': function(opt) {
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
                $('.daiquiri-modal-open').click(function(){
                    new daiquiri.Modal({
                        'url': this.href,
                        'width': 725,
                        'class': 'daiquiri-user-table-modal',
                        'success': daiquiri.data.item.show
                    });
                    return false;
                });
            }
        });    
    }
}
