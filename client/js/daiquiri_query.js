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
        'jobs': baseUrl + '/query/index/list-jobs',
        'browser':baseUrl + '/query/index/database',
        'query': baseUrl + '/query/index/query',
        'download': baseUrl + '/query/index/download',
        'plot': baseUrl + '/query/index/plot',
        'show': baseUrl + '/query/index/show-job',
        'kill': baseUrl + '/query/index/kill-job',
        'remove': baseUrl + '/query/index/remove-job',
        'rename': baseUrl + '/query/index/rename-job',
        'results': {
            'cols': baseUrl + '/data/viewer/cols',
            'rows': baseUrl + '/data/viewer/rows',
            'base': baseUrl
        },
        'fileDownload': baseUrl + '/files/index/row',
        'sampStream': siteUrl + '/query/index/stream',
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

    // set active
    this.idle = true;
};

/**
 * Overrides the submit button of any form with an ajax call.
 */
daiquiri.query.Query.prototype.submitQuery= function(form){  
    var self = this;

    // if the code mirror plugin is present, save its content to the form
    $('textarea', form).daiquiri_codemirror_save();

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

                daiquiri.common.updateCsrf(form, json.csrf);

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

    // prepare dialog
    new daiquiri.Modal({
        'html': '<h2>Query submitted</h2><p>Your query has been submitted as job ' + job.table + '.</p><p>When the job is done you can obtain the results via the job browser on the left side.</p><p><button class="btn btn-primary">Ok</button></p>',
        'width': 600,
    });
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
                console.log($('.daiquiri-modal form'));
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
        url: action + '?mail=1',
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
                self.job = json.data;

                // call display methods
                self.displayDetails();

                // switch to the details tab
                if (self.job.status.value == 'success') {
                    // call display methods
                    self.displayResults();
                    self.displayPlot();
                    self.displayDownload();
                    
                    if (! (self.tabs.details.hasClass('active') || self.tabs.results.hasClass('active') || self.tabs.plot.hasClass('active'))) {
                        $('a', self.header.details).tab('show');
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
            self.jobs = json.data;

            // clean up content
            if ($('#jobs').children().length != 0) {
                $('#jobs').children().remove();
            }

            // construct html string
            var html = '<ul class="nav nav-pills nav-stacked">';
            html += '<li class="nav-header">Jobs</li>';
            $.each(json.data, function (key, value) {
                if(typeof self.job.id != 'undefined' && self.job.id.value == value.id)  {
                    html += '<li class="nav-item active">';
                } else {
                    html += '<li class="nav-item">';
                }

                html += '<a href="#job-' + value.id + '">';
                html += '<span>' + value.table + '</span>';
                html += self.createStatusIcon(value.status);
                html += '</a>';
                html += '</li>';
            });
            html += '<ul>';

            var jobDiv = $('<div/>',{
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
    } else if (status == 'pending') {
        return '<i class="icon-pause pull-right" rel="tooltip" title="Job is pending." data-placement="right"></i>';
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
    $('.daiquiri-query-file-download').remove();

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

                if ($('.daiquiri-table-downloadable','#results-tab').length != 0) {
                    if ($('#daiquiri-file-download-rows-button').length == 0) {
                        $('#results-tab').append('<div><button id="daiquiri-file-download-rows-button" class="linkbutton">Download files from selected rows</button></div>')

                        $('#daiquiri-file-download-rows-button').on('click', function () {
                            var rowIds = [];
                            $('.daiquiri-table-downloadable.daiquiri-table-row-selected','#results-tab').each(function () {

                                rowIds.push($(this).attr('class').match(/daiquiri-table-row-(\d+)/)[1]);
                            })
                            if (rowIds.length != 0) {
                                console.log(rowIds.join(','));
                                // $('<iframe />', {
                                //     'style': 'visibility: hidden; height: 0; width: 0;',
                                //     'src': url
                                // }).appendTo(div);
                            }
                        })
                    }

                    if ($('#daiquiri-file-download-cols-button').length == 0) {
                        $('#results-tab').append('<div><button id="daiquiri-file-download-cols-button" class="linkbutton">Download files from the selected column</button></div>')

                        $('#daiquiri-file-download-cols-button').on('click', function () {
                            var colId = false;
                            var col = $('.daiquiri-table-downloadable.daiquiri-table-col-selected','#results-tab').first();
                            if (col.length != 0) {
                                colId = col.attr('class').match(/daiquiri-table-col-(\d+)/)[1];
                            }
                            if (colId != false) {
                                console.log(colId);
                                // $('<iframe />', {
                                //      'style': 'visibility: hidden; height: 0; width: 0;',
                                //      'src': '<?php echo $this->baseUrl('/files/index/row/id/'); ?>' + colId
                                // }).appendTo('body');
                            }
                        })
                    }
                }

                if ($('#daiquiri-samp-connect').length == 0) {
                    $('#results-tab').append('<div id="daiquiri-samp-connect" class="daiquiri-samp-connect"></div>');

                    var samp = new daiquiri.samp.SAMP($('#daiquiri-samp-connect'),{
                        baseStream: self.url.sampStream
                    });
                }

                // setting SAMP options
                daiquiri.samp.item.table = self.job.table.value;
                daiquiri.samp.item.username = self.job.username.value;
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

/**
 * Displays the download form.
 */
daiquiri.query.Query.prototype.displayDownload = function(){
    var self = this;
    
    if (self.job.status.value != 'success') {
        self.header.download.hide();
    } else {
        self.header.download.show();
        self.tabs.download.children().remove();

        // make ajax call for the download form
        $.ajax({
            url: self.url.download,
            type: 'GET',
            dataType: 'html',
            headers: {
                'Accept': 'application/html'
            },
            data: {
                'table': self.job.table.value
            },
            error: daiquiri.common.ajaxError,
            success: function (html) {

                var div = $('<div/>',{
                    'html' : html
                }).appendTo(self.tabs.download);

                // emulate the form's action with an ajax request
                $('form', div).submit(function(){
                    self.submitDownload(this);
                    return false
                });
            }
        });
    }
};

daiquiri.query.Query.prototype.submitDownload = function(form) {
    var self = this;

    // get the values from the form
    var values = $(form).serialize() + '&table=' + self.job.table.value;

    if (self.idle) {
        self.idle = false;
        $.ajax({
            url: self.url.download,
            type: 'POST',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: values,
            error: daiquiri.common.ajaxError,
            success: function(json) {
                daiquiri.common.updateCsrf($('form', self.tabs.download), json.csrf);
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

    // get the csrf from the form
    self.pendingDownload.data.download_csrf = $('#download_csrf', 'form', self.tabs.download).val();

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
		daiquiri.common.updateCsrf($('form', self.tabs.download), json.csrf);
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
                    'download_format': $('#download_format', 'form', self.tabs.download).val(),
		    'download_csrf': $('#download_csrf', 'form', self.tabs.download).val(),
                    'table': self.job.table.value
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

/**
 * Formats the successfull retrieval of a download link.
 */
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
        'html' : '<p>Your file is beeing created... Please wait...</p>'
    }).appendTo(this.tabs.download);
};

daiquiri.query.Query.prototype.displayDownloadErrors = function (error) {
    if(typeof error == 'undefined') {
        error = '';
    } 

    $('#daiquiri-query-download-pending').remove();
    $('#daiquiri-query-download-link').remove();
    $('<div/>',{
        'id': 'daiquiri-query-download-pending',
        'html' : '<p class="text-error">Your file cannot be created due to an error. Please contact support.<br />' + error + '</p>'
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
