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
daiquiri.query = {};

/**
 * Object to hold the Query instance.
 */
daiquiri.query.item = null;

/**
 * Constructor-like function for the Query class. 
 */
daiquiri.query.Query = function (siteUrl) {
    var self = this;

    // store object globally, kind of a poor mans singleton.
    daiquiri.query.item = this;

    // get the baseUrl and the other urls
    var s = siteUrl.split( '/' );
    if (s.length == 3) {
        var baseUrl = '';
    } else {
        var baseUrl = '/' + siteUrl.split( '/' ).slice(3).join('/')
    }

    this.url = {
        'jobs': baseUrl + '/query/account/list-jobs',
        'browser':baseUrl + '/query/account/databases',
        'query': baseUrl + '/query/form/',
        'download': baseUrl + '/query/download/',
        //'plot': baseUrl + '/query/index/plot',
        'show': baseUrl + '/query/account/show-job',
        'kill': baseUrl + '/query/account/kill-job',
        'remove': baseUrl + '/query/account/remove-job',
        'rename': baseUrl + '/query/account/rename-job',
        'results': {
            'cols': baseUrl + '/data/viewer/cols',
            'rows': baseUrl + '/data/viewer/rows',
            'base': baseUrl
        },
        'rowDownload': baseUrl + '/data/files/row',
        'colDownload': baseUrl + '/data/files/multi',
        'sampStream': siteUrl + '/query/download/stream',
        'register': baseUrl + '/auth/registration/register',
        'baseUrl': baseUrl
    }

    this.job = {};
    this.jobs = {};
    
    this.tabs = {
        'query': $('#query-tab', $('#query')),
        'details': $('#details-tab', $('#query')),
        'results': $('#results-tab', $('#query')),
        'plot': $('#plot-tab', $('#query')),
        'download': $('#download-tab', $('#query'))
    };
    this.header = {
        'query': $('#query-tab-header', $('#query')),
        'details': $('#details-tab-header', $('#query')),
        'results': $('#results-tab-header', $('#query')),
        'plot': $('#plot-tab-header', $('#query')),
        'download': $('#download-tab-header', $('#query'))
    };

    // Object to hold the information to poll a pending a download.
    this.pendingDownload = null;

    // hide some tabs
    self.header.details.hide();
    self.header.results.hide();
    self.header.plot.hide();
    self.header.download.hide();
    
    // display jobs, and bind the poll event on displaying the jobs
    self.displayJobs();
    $(window).bind('poll', function(e) {
        daiquiri.query.item.displayJobs();
    });

    // display database browser
    self.displayBrowser();

    // trigger a resize event when switching to results table
    $('a', self.header.results).on('shown', function (e) {
        $(window).trigger('resize');
    })
    $('a', '#sql-form-tab-header').on('shown', function (e) {
        $(window).trigger('resize');
    })

    // overide the submit button of the form
    $('form', $('#query')).submit(function(){
        self.submitQuery(this);
        return false;
    });

    // overide the button of the download form
    $('button', this.tabs.download).on('click',function(){
        self.submitDownload();
        return false;
    });

    // set active
    this.idle = true;
};

/**
 * Overrides the submit button of any form with an ajax call.
 */
daiquiri.query.Query.prototype.submitQuery= function(form){  
    var self = this;

    // if the code mirror plugin is present, save its content to the form
    $('.codemirror', form).daiquiri_codemirror_save();

    // get the action url and the values from the form
    var action = $(form).attr('action');
    var values = $(form).serialize();

    // emulate the form's action with an ajax request
    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: action,
            type: 'POST',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: values,
            error: daiquiri.common.ajaxError,
            success: function (json){
                // remove old errors
                $('.daiquiri-form-error').remove();

                if (json.status == 'ok') {
                    // store new job and show modal for acknowledgement
                    self.storeNewJob(json.job);
                } else if (json.status == 'plan') {
                    // show the modal with the form for the plan
                    self.displayPlan(json.redirect);
                } else if (json.status == 'error') {
                    // show the errors next to the corresponding fields
                    daiquiri.common.showFormErrors(form, json.errors);
                } else {
                    daiquiri.common.jsonError(json);
                }

                self.idle = true;
            }
        });
    }

    return false;
};

/**
 * Stores the new job locally and displays the acknowlegement modal.
 */
daiquiri.query.Query.prototype.storeNewJob = function(job) {
    // store job
    this.job = job;
    
    // display jobs
    this.displayJobs();
    this.displayBrowser();

    // old job tabs
    this.header.details.hide();
    this.header.results.hide();
    this.header.plot.hide();
    this.header.download.hide();

    // load job if it was an instant success, like with direct (syncronous) querys
    if (job.status === 'success') {
        this.loadJob(job.id);
    } else {
        // prepare dialog
        new daiquiri.Modal({
            'html': '<h2>Query submitted</h2><p>Your query has been submitted as job ' + job.table + '.</p><p>When the job is done you can obtain the results via the job browser on the left side.</p><p><button class="btn btn-primary">Ok</button></p>',
            'width': 600,
        });
    }
};

/**
 * Fetches the form for the plan, display it an a modal and ajaxifies the form buttons.
 */
daiquiri.query.Query.prototype.displayPlan = function(redirect){ 
    var self = this;

    new daiquiri.Modal({
        'url': redirect,
        'width': 720,
        'success': function (json) {
            if (typeof json === 'undefined') {
                // the mail button was clicked!
                self.mailPlan($('.daiquiri-modal form'));
            } else {
                // the form was submitted
                self.storeNewJob(json.job);  
            }
        }                    
    });
};

/**
 * Redirects to the plan mail form.
 */
daiquiri.query.Query.prototype.mailPlan = function(form){ 
     // if the code mirror plugin is present, save its content to the form
    $('#plan_query').daiquiri_codemirror_save();

    // get the action url and the values from the form
    var action = $(form).attr('action');
    var values = $(form).serialize();

    $.ajax({
        url: action + '&mail=1',
        type: 'POST',
        dataType: 'json',
        headers: {
            'Accept': 'application/json'
        },
        data: values,
        error: daiquiri.common.ajaxError,
        success: function (json){
            window.location = json.redirect;
        }
    });
};

/**
 * Loads a specified job with an ajax call.
 */
daiquiri.query.Query.prototype.loadJob = function(jobId){ 
    var self = this;

    // remove old download link
    $('#daiquiri-query-download-link').remove();

    // make ajax request for the job
    $.ajax({
        url: self.url.show,
        type: 'POST',
        dataType: 'json',
        headers: {
            'Accept': 'application/json'
        },
        data: {
            'id': jobId
        },
        error: daiquiri.common.ajaxError,
        success: function (json) {
            if (json.status == 'ok') {
                self.job = json.job;

                // call display methods
                self.displayDetails();

                // show the download tab
                self.header.download.show();

                // switch to the details tab
                if (self.job.status.value == 'success') {
                    // call display methods
                    self.displayResults();
                    self.displayPlot();
                    
                    if (! (self.tabs.details.hasClass('active') || self.tabs.results.hasClass('active') || self.tabs.plot.hasClass('active'))) {
                        $('a', self.header.results).tab('show');
                    }

                } else {
                    // hide other tabs
                    self.header.results.hide();
                    self.header.plot.hide();
                    self.header.download.hide();
                    
                    // switch to the details tab
                    $('a', self.header.details).tab('show');
                }
            } else {
                daiquiri.common.jsonError(json);
            }  
        }
    });
};

/**
 * Displays all jobs in the corresponding tab.
 */
daiquiri.query.Query.prototype.displayJobs = function(){
    var self = this;

    // make ajax request for the list of jobs
    $.ajax({
        url: self.url.jobs,
        dataType: 'json',
        headers: {
            'Accept': 'application/json'
        },
        error: daiquiri.common.ajaxError,
        success: function (json){
            self.jobs = json.jobs;

            // clean up content
            if ($('#jobs').children().length != 0) {
                $('#jobs').children().remove();
            }

            // build queue status
            if (typeof json.message !== 'undefined' && json.message !== false 
                || typeof json.nactive !== 'undefined' && json.nactive !== false
                || typeof json.guest !== 'undefined' && json.guest !== false ) {

                var html = '<ul class="nav nav-pills nav-stacked">';
                html += '<li class="nav-header">Database status</li>';
                html += '<li class="nav-item">';

                // status message
                if (typeof json.message !== 'undefined' && json.message !== false) {
                    html += '<p>' + json.message + '</p>';
                }

                // number of jobs in the queue
                if (typeof json.nactive !== 'undefined' && json.nactive !== false) {
                    if (json.nactive === 1) {
                        html += '<p>There is ' + json.nactive + ' job in the queue.</p>';
                    } else {
                        html += '<p>There are ' + json.nactive + ' jobs in the queue.</p>';
                    }
                }

                // guest user warning
                if (typeof json.guest !== 'undefined' && json.guest !== false) {
                    html += '<p>You are using the guest user. For a personal account, please sign up <a href="' + self.url.register + '">here</a>.</p>';
                }

                // quota information
                if (typeof json.quota !== 'undefined' && json.quota !== false) {
                    html += '<p class="' + ((json.quota.exeeded) ? 'text-error' : '') + '">';
                    if (typeof json.guest !== 'undefined' && json.guest !== false) {
                        html += 'The guest user is using ' + json.quota.used + ' of its quota of ' + json.quota.max + '.';
                    } else {
                        html += 'You are using ' + json.quota.used + ' of your quota of ' + json.quota.max + '. ';
                    }
                    if (json.quota.exeeded) {
                        html += 'Please remove some jobs to free space or contact the administrators.'
                    }
                    html += '</p>';
                }

                html += '</li>';
                html += '</ul>';
                $('<div/>',{
                    'class': 'daiquiri-widget',
                    'html' : html
                }).appendTo($('#jobs'));
            }

            // build job list
            var html = '<ul class="nav nav-pills nav-stacked">';
            html += '<li class="nav-header">Jobs</li>';
            $.each(self.jobs, function (key, value) {
                if(typeof self.job.id != 'undefined' && self.job.id.value == value.id)  {
                    html += '<li class="nav-item active">';
                } else {
                    html += '<li class="nav-item">';
                }

                html += '<a href="#job-' + value.id + '">';
                html += self.createStatusIcon(value.status);
                html += '<div class="daiquiri-query-jobs-item">' + value.table + '</div>';
                html += '</a>';
                html += '</li>';
            });
            html += '</ul>';

            var jobDiv = $('<div/>',{
                'class': 'daiquiri-widget',
                'html' : html
            }).appendTo($('#jobs'));

            // activate tooltips
            $('i','#jobs').tooltip();

            $.each($('a',jobDiv),function(){  
                // load job on click
                $(this).click(function() {
                    self.pendingDownload = null;

                    var jobId = $(this).attr('href').split("-")[1];
                    
                    $('.active',$(this).parent().parent()).removeClass('active');
                    $(this).parent().addClass('active');

                    self.loadJob(jobId);

                    return false;
                });
            });
        }
    });
};

/**
 * Create the icon corresponding to the status of a job.
 */
daiquiri.query.Query.prototype.createStatusIcon = function(status){
    if (status == 'success') {
        return '<i class="icon-ok pull-right" rel="tooltip" title="Job is done" data-placement="right"></i>';
    } else if (status == 'error') {
        return '<i class="icon-warning-sign pull-right rel="tooltip" title="Job terminated with an error." data-placement="right"></i>';
    } else if (status == 'running') {
        return '<i class="icon-play pull-right rel="tooltip" title="Job is running." data-placement="right"></i>';
    } else if (status == 'killed') {
        return '<i class="icon-remove pull-right" rel="tooltip" title="Job was killed." data-placement="right"></i>';
    } else if (status == 'deleted') {
        return '<i class="icon-trash pull-right" rel="tooltip" title="Job was deleted." data-placement="right"></i>';
    } else if (status == 'queued') {
        return '<i class="icon-pause pull-right" rel="tooltip" title="Job is queued." data-placement="right"></i>';
    } else if (status == 'timeout') {
        return '<i class="icon-ban-circle pull-right" rel="tooltip" title="Job timed out." data-placement="right"></i>';
    } else {
        return '<i class="icon-question-sign pull-right" rel="tooltip" title="Job status is unknown." data-placement="right"></i>';
    };
}

/**
 * Displays the database browser.
 */
daiquiri.query.Query.prototype.displayBrowser = function(){
    var self = this;

    $('#browser').daiquiri_browser({
        'url': self.url.browser,
        'columns': ['databases','tables','columns'],
        'dblclick': function (opt) {
            var string;
            if (typeof opt.left !== 'undefined') {
                if (typeof opt.center === 'undefined' ) {
                    string = '`' + opt.left + '`';
                } else {
                    if (typeof opt.right === 'undefined' ) {
                        string = '`' + opt.left + '`.`' + opt.center + '`';
                    } else {
                        string = '`' + opt.right + '`';
                    }
                }
            }

            $('#sql_query').insertAtCaret(string);
            $('#sql_query').daiquiri_codemirror_insertAtCaret(string);
        }
    });
}

/**
 * Displays the loaded job in the corresponding tab.
 */
daiquiri.query.Query.prototype.displayDetails = function(){
    var self = this;
    self.header.details.show();
    
    // clean up content
    self.tabs.details.children().remove();

    // prepare details table
    var html = '<table class="table table-bordered" style="table-layout: fixed;"><tbody>';
    $.each(self.job, function(key,element) {
        html += '<tr>'
        html += '<td class="onehundredfifty">' + element.name + '</td>';
        if(element.key == "query" || element.key == "actualQuery") {
            //                html += '<td><div class="cm-s-default" style="overflow:auto" id="jobDet_' + element.key + '">' + element.value.replace(/\n/g, '<br />') + '</div></td>';
            html += '<td><div class="cm-s-default" style="overflow:auto" id="jobDet_' + element.key + '">' + element.value.replace(/\n/g, '<br />') + '</div></td>';
        } else {
            html += '<td>' + element.value + '</td>';
        }
        html += '</tr>';
    });
    html += '</tbody></table>';
    
    // prepare links
    if(self.job.status.value == 'success') {
        html += '<p><a class="rename-table" href="#rename-job-' + self.job.id.value + '">Rename table</a></p>';
    }
    if (self.job.status.value == 'running' || self.job.status.value == 'pending') {
        html += '<p><a class="kill-job" href="#kill-job-' + self.job.id.value + '">Kill job</a></p>';
    } else {
        html += '<p><a class="remove-job" href="#remove-job-' + self.job.id.value + '">Remove job</a></p>';
    }

    $('<div/>',{
        'html' : html
    }).appendTo(this.tabs.details);

    if($('#jobDet_query').length != 0)
        CodeMirror.runMode($('#jobDet_query').text(), "text/x-mysql", $('#jobDet_query')[0]);
    if($('#jobDet_actualQuery').length != 0)
        CodeMirror.runMode($('#jobDet_actualQuery').text(), "text/x-mysql", $('#jobDet_actualQuery')[0]);

    // overide rename link with ajax call
    $('a.rename-table').click(function() {
        self.renameJob();
        return false;
    });

    // overide kill link with ajax call
    $('a.kill-job').click(function() {
        self.killJob();
        return false;
    });
    
    // overide remove link with ajax call
    $('a.remove-job').click(function() {
        self.removeJob();
        return false;
    });
};

/**
 * Displays the result table of the loaded job in the corresponding tab.
 */
daiquiri.query.Query.prototype.displayResults = function(){
    var self = this;
    
    // remove any old buttons or iframes
    $('#daiquiri-file-download-buttons').remove();

    if (self.job.status.value != 'success') {
        self.header.results.hide();
    } else {
        self.header.results.show();

        $('#results-table').daiquiri_table({
            'params': {
                'db': self.job.database.value,
                'table': self.job.table.value
            },
            'rowsurl': self.url.results.rows,
            'colsurl': self.url.results.cols,
            'baseurl': self.url.results.base,
            'width': '700px',
            'multiselect': true,
            'success': function (table) {
                // enable image viewer for possible images
                $('#results-table').daiquiri_imageview();

                // create download-buttons div if it is not already there
                if ($('#daiquiri-file-download-buttons').length == 0) {
                    $('#results-tab').append('<div id="daiquiri-file-download-buttons"><p id="daiquiri-file-download-message" class="help-block"></p><p id="daiquiri-file-download-cols-button"></p><p id="daiquiri-file-download-rows-button"></p></div>');
                }

                // display download message if files can be downloaded
                if ($('#results-tab .daiquiri-table-downloadable').length != 0) {
                    $('#daiquiri-file-download-message').html("To download all files in one or more rows, please select the corresponding rows by mouse click. In order to download all files in a particular column, please select the column by a mouse click on the column's title.");
                } else {
                    $('#daiquiri-file-download-message').html('');
                    $('#daiquiri-file-download-cols-button').html('');
                    $('#daiquiri-file-download-rows-button').html('');
                }

                // enable controls for downloading all file in a column
                $('#results-table').on('click', function () {
                    if ($('#results-tab .daiquiri-table-downloadable').length != 0) {
                        var ncols = $('#results-tab .daiquiri-table-col-selected').length;
                        var nrows = $('#results-tab .daiquiri-table-row-selected').length;
                        var self = daiquiri.query.item;

                        // create download button for the column
                        if (ncols != 0) {
                            var col = $('#results-tab th.daiquiri-table-col-selected');
                            var colId = col.attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                            var colname = daiquiri.table.items['results-table'].colsmodel[colId].name;

                            if (typeof daiquiri.table.items['results-table'].colsmodel[colId].format !== 'undefined' &&
                                typeof daiquiri.table.items['results-table'].colsmodel[colId].format.type !== 'undefined' &&
                                daiquiri.table.items['results-table'].colsmodel[colId].format.type == 'filelink') {

                                $('#daiquiri-file-download-cols-button').html('<button class="linkbutton">Download all files from column `' + colname + '`</button>');

                                $('#daiquiri-file-download-cols-button button').on('click', function () {
                                    var colId = false;
                                    var col = $('.daiquiri-table-downloadable.daiquiri-table-col-selected','#results-tab').first();
                                    if (col.length != 0) {
                                        colId = col.attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                                    }
                                    if (colId != false) {
                                        var tablename = self.job.table.value;
                                        var colname = daiquiri.table.items['results-table'].colsmodel[colId].name;
                                        var url = self.url.colDownload + '?table=' + tablename + '&column=' + colname;
                                        $('<iframe />', {
                                             'style': 'visibility: hidden; height: 0; width: 0;',
                                             'src': url
                                        }).appendTo('body');
                                    }
                                });
                            } else {
                                $('#daiquiri-file-download-cols-button').html('');
                            }
                        } else {
                            $('#daiquiri-file-download-cols-button').html('');
                        }

                        // create download button for the rows
                        if (nrows != 0) {
                            $('#daiquiri-file-download-rows-button').html('<button class="linkbutton">Download files from selected row(s)</button>');

                            $('#daiquiri-file-download-rows-button button').on('click', function () {
                                var rowIds = [];
                                $('.daiquiri-table-downloadable.daiquiri-table-row-selected','#results-tab').each(function () {
                                    rowIds.push($(this).attr('class').match(/daiquiri-table-row-(\d+)/)[1]);
                                });

                                if (rowIds.length != 0) {
                                    // get only unique entries
                                    rowIds = rowIds.filter(function(itm,i,a) {
                                        return i==a.indexOf(itm);
                                    });

                                    var tablename = self.job.table.value;
                                    var url = self.url.rowDownload + '?table=' + tablename + '&id=' + rowIds.join(',');

                                    $('<iframe />', {
                                         'style': 'visibility: hidden; height: 0; width: 0;',
                                         'src': url
                                    }).appendTo('body');
                                }
                            });
                        } else {
                            $('#daiquiri-file-download-rows-button').html('');
                        }
                    }
                });

                // create samp button if the placeholder is there
                if ($('#daiquiri-samp').length != 0) {
                    if ($('#daiquiri-samp-connect').length == 0) {
                        $('#results-tab').append('<div id="daiquiri-samp-connect" class="daiquiri-samp-connect"></div>');

                        var samp = new daiquiri.samp.SAMP($('#daiquiri-samp-connect'),{
                            baseUrl: self.url.baseUrl,
                            baseStream: self.url.sampStream
                        });
                    }

                    // setting SAMP options
                    daiquiri.samp.item.table = self.job.table.value;
                    daiquiri.samp.item.username = self.job.username.value;
                }
            }
        });
    }
};

/**
 * Displays a plot canvas with a corresponding form.
 */
daiquiri.query.Query.prototype.displayPlot = function(){
    var self = this;
    
    if (self.job.status.value != 'success') {
        self.header.plot.hide();
    } else {
        self.header.plot.show();

        $('#plot').daiquiri_plot({
            'params': {
                'db': self.job.database.value,
                'table': self.job.table.value
            },
            'rowsurl': self.url.results.rows,
            'colsurl': self.url.results.cols,
            'form': $('#plot-form')
        });
    }
};

daiquiri.query.Query.prototype.submitDownload = function() {
    var self = this;

    // get the values from the form
    var table = self.job.table.value;
    var format = $('#download_format').val();

    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: self.url.download,
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                'table': table,
                'format': format
            },
            error: daiquiri.common.ajaxError,
            success: function(json) {
                self.initDownload(json);
            }
        });
    }
}

daiquiri.query.Query.prototype.regenerateDownload = function(link) {
    var self = this;

    // perform a ajax call to regenerate the download
    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: link,
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            error: daiquiri.common.ajaxError,
            success: function(json) {
                self.initDownload(json);
            }
        });
    }
};

daiquiri.query.Query.prototype.pollDownload = function(){
    var self = daiquiri.query.item;

    // check if polling was disabled already
    if (self.pendingDownload == null) {
        $(window).unbind('poll', daiquiri.query.item.pollDownload);
        return;
    }

    // check if the job is completed
    if (self.job.status.value != 'success') {
        self.pendingDownload == null;
        return;
    }

    // make an ajax call to get the updated status of the download
    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: self.url.download,
            type: 'POST',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: self.pendingDownload.data,
            error: daiquiri.common.ajaxError,
            success: function(json) {
                self.initDownload(json);
            }
        });
    }
};

daiquiri.query.Query.prototype.initDownload = function(json) {
    var self = this;

    if (json.status == 'ok') {
        self.pendingDownload = null;
        self.displayDownloadLink(json);
    } else if (json.status == 'pending') {
        if(self.pendingDownload == null) {
            self.pendingDownload = {
                'data': {
                    'table': self.job.table.value,
                    'format': $('#download_format').val()
                }
            };

            $(window).bind('poll', daiquiri.query.item.pollDownload);

            self.displayDownloadPendingMessage();
        }
    } else if (json.status == 'error') {
        self.pendingDownload = null;
        self.displayDownloadErrors(json.errors.form);
    } else {
        self.pendingDownload = null;
        daiquiri.common.jsonError(json);
    }

    // since this is supposed to be the success function of an ajax call, we set idle to true again.
    self.idle = true;
}

daiquiri.query.Query.prototype.createDownloadLink = function (link) { 
    var html = '<p>You can now download the file using:</p>';
    html += '<p><a target="_blank" href="' + link + '">';
    html += link;
    html += '</a></p>';
    html += '<p>or <a target="_self" id="regenerate-download" href="javascript: void(0)">regenerate</a> the file.</p>';
    return html;
}

daiquiri.query.Query.prototype.displayDownloadLink = function(json) {
    var self = this;

    // remove old download messages and links
    $('#daiquiri-query-download-pending').remove();
    $('#daiquiri-query-download-link').remove();

    // create new space for download messages
    $('<div/>',{
        'id': 'daiquiri-query-download-link',
        'html' : self.createDownloadLink(json.link)
    }).appendTo(self.tabs.download);

    // overide the click on the renerate link
    $('#regenerate-download').click(function(){
        self.regenerateDownload(json.regenerateLink);
        return false;
    });
};

daiquiri.query.Query.prototype.displayDownloadPendingMessage = function() {
    // remove old download messages and links
    $('#daiquiri-query-download-pending').remove();
    $('#daiquiri-query-download-link').remove();

    // create new space for download messages
    $('<div/>',{
        'id': 'daiquiri-query-download-pending',
        'html' : '<p>Your file is being created... Please wait...</p>'
    }).appendTo(this.tabs.download);
};

daiquiri.query.Query.prototype.displayDownloadErrors = function (error) {
    if(typeof error == 'undefined') {
        error = {'form': ''};
    } 

    $('#daiquiri-query-download-pending').remove();
    $('#daiquiri-query-download-link').remove();
    $('<div/>',{
        'id': 'daiquiri-query-download-pending',
        'html' : '<p class="text-error">Your file cannot be created due to an error. Please contact support.<br />' + error.form + '</p>'
    }).appendTo(this.tabs.download);
};

daiquiri.query.Query.prototype.removeJob = function () {
    var self = this;

    // prepare dialog
    new daiquiri.Modal({
        'url': self.url.remove + '?id=' + self.job.id.value,
        'success': function () {
            //switch to query tab
            $('a', '.default-tab').tab('show');

            // hide job tabs
            self.header.details.hide();
            self.header.results.hide();
            self.header.plot.hide();
            self.header.download.hide();

            // reload jobs
            self.displayJobs();
        }
    });
};

daiquiri.query.Query.prototype.killJob = function () {
    var self = this;

    // prepare dialog
    new daiquiri.Modal({
        'url': self.url.kill + '?id=' + self.job.id.value,
        'success': function () {
            // switch to query tab
            $('a', '.default-tab').tab('show');

            // hide job tabs
            self.header.details.hide();
            self.header.results.hide();
            self.header.plot.hide();
            self.header.download.hide();

            // reload jobs
            self.displayJobs();
        }
    });
};

daiquiri.query.Query.prototype.renameJob = function () {
    var self = this;

    // prepare dialog
    new daiquiri.Modal({
        'url': self.url.rename + '?id=' + self.job.id.value,
        'width': 700,
        'success': function () {
            // reload jobs
            self.displayDetails();
            self.displayJobs();

            // reload the current job
            self.loadJob(self.job.id.value)
        }
    });
}
