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

var _daiquiri_query;

function Daiquiri_Query(baseUrl) {
    this.url = {
        'jobs': baseUrl + '/query/index/list-jobs',
        'browser': baseUrl + '/query/index/database',
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
        'fileDownload': baseUrl + '/files/index/row'
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

    this.downloadPoll = {
        handler : function (e) {
            _daiquiri_query._getDownloadStatus();
        }, 
        idle : true,
        data : ''
    }

    /**
     * Initializes the query form
     */ 
    this.init = function() {
        var self = this;

        // hide some tabs
        self.header.details.hide();
        self.header.results.hide();
        self.header.plot.hide();
        self.header.download.hide();
        
        // display jobs, and bind the poll event on displaying the jobs
        self.displayJobs();
        $(window).bind('poll', function(e) {
            _daiquiri_query.displayJobs();
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
            self.submit(this);
            return false; // return false to disable ordenary html form request
        });
    };

    /**
     * Overrides the submit button of any form with an ajax call.
     */
    this.submit= function(form){  
        var self = this;
	
        //if the code mirror plugin is present, save its content to the form
        if (typeof _daiquiri_cm !== "undefined") {
            _daiquiri_cm.save();
        }

        // remove old description from ALL forms
        $('.daiquiri-form-error').remove();
        
        // get the action url from the form
        var action = $(form).attr('action');
        
        // get the values from the form
        var values = $(form).serialize();
        
        // emulate the form's action with an ajax request
        $.ajax({
            url: action,
            type: 'POST',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: values,
            error: daiquiri_ajaxError,
            success: function (json){
                if (json.status == 'ok') {
                    // store job
                    self.job = json.job;
                    
                    // display jobs
                    self.displayJobs();
                    self.displayBrowser();

                    // prepare dialog
                    $('.daiquiri-modal-dialog').remove();
                    var body = '<p>Your query has been submitted as job ' + self.job.table + '.</p>';
                    body += '<p>When the job is done you can obtain the results ';
                    body += 'via the job browser on the left side.</p>';
                    var dialog = self._getModal({
                        'label': 'Query submitted',
                        'body': body,
                        'primary': 'Ok'
                    });
                    dialog.appendTo($('#query'));
                    dialog.modal();

                } else if (json.status == 'redirect') {
                    $.ajax({
                        url: json.redirect,
                        type: 'GET',
                        dataType: 'text',
                        headers: {
                            'Accept': 'application/html'
                        },
                        data: values,
                        error: daiquiri_ajaxError,
                        success: function (html){
                            // prepare dialog
                            $('.daiquiri-modal-dialog').remove();
                            var dialog = self._getModal({
                                'label': 'Query plan',
                                'body': html
                            });
                            dialog.appendTo($('#query'));
                            dialog.css('width', '730px');
                            //must be half of the width, minus scrollbar on the left (30px)
                            dialog.css('margin', '-250px 0 0 -365px');

                            var _daiquiri_cm_plan;
                
                            _daiquiri_cm_plan = CodeMirror.fromTextArea($('#plan_query').get(0), {
                                mode: 'text/x-mysql',
                                indentWithTabs: false,
                                smartIndent: true,
                                matchBrackets : true,
                                lineNumbers: true,
                                lineWrapping: true
                            });
                                
                            _daiquiri_cm_plan.setSize($('#plan_query').css('width'),null);

                            // show code mirror plugin for plan
                            dialog.on('shown', function () {
                                _daiquiri_cm_plan.refresh();
                            });
                            
                            dialog.modal();

                            $('#plan_submit', dialog).click(function(){
                                // get values from plan form
                                _daiquiri_cm_plan.save();

                                var planValues = $('form', dialog).serialize();

                                $.ajax({
                                    url: json.redirect,
                                    type: 'POST',
                                    dataType: 'json',
                                    headers: {
                                        'Accept': 'application/json'
                                    },
                                    data: planValues,
                                    error: daiquiri_ajaxError,
                                    success: function (json){
                                        if (json.status == 'ok') {
                                            // hide modal
                                            $('.daiquiri-modal-dialog').modal('hide');
                                            
                                            // store job
                                            self.job = json.job;
                    
                                            // display jobs
                                            self.displayJobs();
                                            self.displayBrowser();

                                            // prepare dialog
                                            $('.daiquiri-modal-dialog').remove();
                                            var body = '<p>Your query has been submitted as job ' + self.job.table + '.</p>';
                                            body += '<p>When the job is done you can obtain the results ';
                                            body += 'via the job browser on the left side.</p>';
                                            var dialog = self._getModal({
                                                'label': 'Query submitted',
                                                'body': body,
                                                'primary': 'Ok'
                                            });
                                            dialog.appendTo($('#query'));
                                            dialog.modal();
                                        } else {
                                            daiquiri_jsonError(json);
                                        }   
                                    }
                                });
                                
                                return false; // return false to disable ordenary html form request
                            });
                            $('#plan_mail', dialog).click(function(){
                                // get values from plan form
                                _daiquiri_cm_plan.save();

                                var planValues = $('form', dialog).serialize();
                                planValues += '&plan_mail=1'

                                $.ajax({
                                    url: json.redirect,
                                    type: 'POST',
                                    dataType: 'json',
                                    headers: {
                                        'Accept': 'application/json'
                                    },
                                    data: planValues,
                                    error: daiquiri_ajaxError,
                                    success: function (json){
                                        window.location = json.redirect;
                                    }
                                });
                                
                                return false; // return false to disable ordenary html form request
                            });
                            $('#plan_cancel', dialog).click(function(){
                                dialog.modal('hide');
                                return false; // return false to disable ordenary html form request
                            });
                        }
                    });
                } else if (json.status == 'error') {
                    // put errors in form description
                    $('<p/>',{
                        'class' : 'daiquiri-form-error text-error',
                        'html': json.errorString
                    }).appendTo($(form));
                } else if (json.status == 'form') {
                    $.each(json.errors, function (key, value) {
                        if (key != 'form') {
                            var element;
                            var html;
                            if ($('#'+key).parents('.daiquiri-form-horizontal-group').length != 0) {
                                element = $('#'+key);
                                html = '<ul class="daiquiri-form-error unstyled text-error help-inline">';
                            } else if ($('#'+key).parents('.daiquiri-form-view-script-group').length != 0) {
                                element = $('.daiquiri-form-view-script-error', $('#'+key).parent());
                                html = '<ul class="daiquiri-form-error unstyled text-error">';
                            } else {
                                element = $('#'+key).parent();
                                html = '<ul class="daiquiri-form-error unstyled text-error">';
                            }
                            $.each(value, function (key, value) {
                                html += '<li>' + value + '</li>';
                            });
                            html += '</ul>';

                            element.after(html);
                            element.addClass('error');
                        } else {
                            var html = '<ul class="daiquiri-form-error unstyled text-error">';
                            $.each(value, function (key, value) {
                                html += '<li>' + value + '</li>';
                            });
                            html += '</ul>';
                            $(form).append(html);
                        }
                    });
                } else {
                    daiquiri_jsonError(json);
                }   
            }
        });
    };
    
    /**
     * Loads a specified job with n ajax call.
     */
    this.loadJob= function(jobId){ 
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
            error: daiquiri_ajaxError,
            success: function (json) {
                if (json.status == 'ok') {
                    self.job = json.data;

                    // insert original query in query form
                    $('#sql_query').text(self.job.query.value);
                    
                    // call display methods
                    self.displayDetails();

                    // switch to the details tab
                    if (self.job.status.value == 'success') {
                        // call display methods
                        self.displayResults();
                        self.displayPlot();
                        self.displayDownload();
                        
                        // switch to the results tab
                        $('a', self.header.results).tab('show');
                    } else {
                        // hide other tabs
                        self.header.results.hide();
                        self.header.plot.hide();
                        self.header.download.hide();
                        
                        // switch to the details tab
                        $('a', self.header.details).tab('show');
                    }
                } else if (json.status == 'error') {
                    self.queryError(json);
                } else {
                    daiquiri_jsonError(json);
                }  
            }
        });
    };
    
    /**
     * Displays all jobs in the corresponding tab.
     */
    this.displayJobs = function(){
        var self = this;

        // make ajax request for the list of jobs
        $.ajax({
            url: self.url.jobs,
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            error: daiquiri_ajaxError,
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
                    html += self._getStatusIcon(value.status);
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
                        self.downloadPoll.idle = true;

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
     * Displays the databases browser.
     */
    this.displayBrowser = function(){
        var self = this;

        $('#browser').daiquiri_browser({
            'url': self.url.browser,
            'action': function (string) {
                $('#sql_query').insertAtCaret(string);

                if(typeof _daiquiri_cm !== undefined) {
                    var pos = _daiquiri_cm.getCursor();
                    pos['ch'] += string.length;
                    _daiquiri_cm.replaceSelection(string);
                    _daiquiri_cm.setCursor(pos);
                    _daiquiri_cm.focus();
                }
            }
        });
    }

    /**
     * Displays the loaded job in the corresponding tab.
     */
    this.displayDetails = function(){
        var self = this;
        self.header.details.show();
        
        // clean up content
        if (self.tabs.details.children().length != 0) {
            self.tabs.details.children().remove();
        }

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
            var jobId = $(this).attr('href').split("-")[2];
            self.renameJob(jobId);
            return false;
        });

        // overide kill link with ajax call
        $('a.kill-job').click(function() {
            var jobId = $(this).attr('href').split("-")[2];
            self.killJob(jobId);
            return false;
        });
        
        // overide remove link with ajax call
        $('a.remove-job').click(function() {
            var jobId = $(this).attr('href').split("-")[2];
            self.removeJob(jobId);
            return false;
        });
    };
    
    /**
     * Displays the result table of the loaded job in the corresponding tab.
     */
    this.displayResults = function(){
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
                'callback': function (daiquiriTable) {
                    if (daiquiriTable.opt.select == true) {
                        var div = $('<div />', {
                            'class': 'daiquiri-query-file-download',
                            'html': '<button class="btn daiquiri-query-file-download-button">Download files from selected rows</button>'
                        }).appendTo($('#results-tab'));
                        $('.daiquiri-query-file-download-button').click(function(){
                            var ids = $('#results-table-table').getGridParam('selarrrow').join();
                            var url = self.url.fileDownload + '?table=' + self.job.table.value + '&id=' + ids;
                
                            $('<iframe />', {
                                'style': 'visibility: hidden; height: 0; width: 0;',
                                'src': url
                            }).appendTo(div);
                        })
                    }
                }
            });
        }
    };
    
    /**
     * Displays 
     */
    this.displayPlot = function(){
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
     * Displays 
     */
    this.displayDownload = function(){
        var self = this;
        
        if (self.job.status.value != 'success') {
            self.header.download.hide();
        } else {
            self.header.download.show();
            self.tabs.download.children().remove();
 
            // make ajax call for the download options
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
                error: daiquiri_ajaxError,
                success: function (html) {
                    var div = $('<div/>',{
                        'html' : html
                    }).appendTo(self.tabs.download);
                
                    // emulate the form's action with an ajax request
                    var form = $('form', div);
                    form.submit(function(){
                        $.ajax({
                            url: self.url.download,
                            type: 'POST',
                            dataType: 'json',
                            headers: {
                                'Accept': 'application/json'
                            },
                            data: {
                                'format': $('#format', form).val(),
                                'table': self.job.table.value
                            },
                            error: daiquiri_ajaxError,
                            success: function (json){
                                var html = '';
                                if (json.status == 'ok') {
                                    html = self._getDownloadLink(json);

                                    self.downloadPoll.idle = true;

                                    $('#daiquiri-query-download-pending').remove();
                                    $('#daiquiri-query-download-link').remove();
                                    $('<div/>',{
                                        'id': 'daiquiri-query-download-link',
                                        'html' : html
                                    }).appendTo(self.tabs.download);

                                    $('#regenLink').click(function(e){
                                        e.preventDefault();
                                        $.ajax({
                                            url: json.regenLink,
                                            dataType: 'json',
                                            headers: {
                                                'Accept': 'application/json'
                                            },
                                            error: daiquiri_ajaxError,
                                            success: function (json) {
                                                if (json.status == 'ok') {
                                                    html = self._getDownloadLink(json);

                                                    self.downloadPoll.idle = true;

                                                    $('#daiquiri-query-download-pending').remove();
                                                    $('#daiquiri-query-download-link').remove();
                                                    $('<div/>',{
                                                        'id': 'daiquiri-query-download-link',
                                                        'html' : html
                                                    }).appendTo(self.tabs.download);
                                                } else if (json.status == 'pending') {
                                                    var data = {
                                                        'format': $('#format', form).val(),
                                                        'table': self.job.table.value
                                                    };

                                                    self.downloadPoll.data = data;

                                                    if(self.downloadPoll.idle == true) {
                                                        self.downloadPoll.idle = false;
                                                        $(window).bind('poll', _daiquiri_query.downloadPoll.handler());
                                                    }

                                                    $('#daiquiri-query-download-pending').remove();
                                                    $('#daiquiri-query-download-link').remove();
                                                    $('<div/>',{
                                                        'id': 'daiquiri-query-download-pending',
                                                        'html' : '<p>Your file is beeing created... Please wait...</p>'
                                                    }).appendTo(self.tabs.download);
                                                } else if (json.status == 'error') {
                                                    self.downloadPoll.idle = true;

                                                    var err = '';
                                                    if(typeof json.error != 'undefined') {
                                                        err = json.error;
                                                    } 

                                                    $('#daiquiri-query-download-pending').remove();
                                                    $('#daiquiri-query-download-link').remove();
                                                    $('<div/>',{
                                                        'id': 'daiquiri-query-download-pending',
                                                        'html' : '<p>Your file cannot be created due to an error. Please contact support.<br />' + err + '</p>'
                                                    }).appendTo(self.tabs.download);
                                                } else {
                                                    self.downloadPoll.idle = true;

                                                    daiquiri_jsonError(json);
                                                }
                                            }
                                        })
                                    });

                                } else if (json.status == 'pending') {
                                    var data = {
                                        'format': $('#format', form).val(),
                                        'table': self.job.table.value
                                    };

                                    self.downloadPoll.data = data;

                                    if(self.downloadPoll.idle == true) {
                                        self.downloadPoll.idle = false;
                                        $(window).bind('poll', _daiquiri_query.downloadPoll.handler());
                                    }

                                    $('#daiquiri-query-download-pending').remove();
                                    $('#daiquiri-query-download-link').remove();
                                    $('<div/>',{
                                        'id': 'daiquiri-query-download-pending',
                                        'html' : '<p>Your file is beeing created... Please wait...</p>'
                                    }).appendTo(self.tabs.download);
                                } else if (json.status == 'error') {
                                    self.downloadPoll.idle = true;

                                    var err = '';
                                    if(typeof json.error != 'undefined') {
                                        err = json.error;
                                    } 

                                    $('#daiquiri-query-download-pending').remove();
                                    $('#daiquiri-query-download-link').remove();
                                    $('<div/>',{
                                        'id': 'daiquiri-query-download-pending',
                                        'html' : '<p>Your file cannot be created due to an error. Please contact support.<br />' + err + '</p>'
                                    }).appendTo(self.tabs.download);
                                } else {
                                    self.downloadPoll.idle = true;

                                    daiquiri_jsonError(json);
                                }
                            }
                        });
                    
                        return false;
                    });
                }
            });
        }
    };
    
    this.removeJob = function (jobId) {
        var self = this;
        
        // prepare dialog
        $('.daiquiri-modal-dialog').remove();
        var dialog = self._getModal({
            'id': 'remove-job-' + self.job.id.value + '-modal',
            'label': 'Really remove job?',
            'danger': 'Remove job',
            'button': 'Cancel'
        });
        dialog.appendTo($('#query'));
        dialog.modal();

        $('#daiquiri-modal-danger').click(function() {
            var jobId = $('.daiquiri-modal-dialog').attr('id').split("-")[2];

            $.ajax({
                url: self.url.remove,
                type: 'POST',
                dataType: 'json',
                data: {
                    'id': jobId
                },
                headers: {
                    'Accept': 'application/json'
                },
                error: daiquiri_ajaxError,
                success: function (json){
                    if (json.status == 'ok') {
                        // swich to query tab
                        $('a', self.header.query).tab('show');
                            
                        // hide job tabs
                        self.header.details.hide();
                        self.header.results.hide();
                        self.header.plot.hide();
                        self.header.download.hide();
                            
                        // reload jobs
                        self.displayJobs();
                    } else {
                        daiquiri_jsonError(json);
                    }
                }
            });
        });
    }
    
    this.killJob = function (jobId) {
        var self = this;
        
        // prepare dialog
        $('.daiquiri-modal-dialog').remove();
        var dialog = self._getModal({
            'id': 'kill-job-' + self.job.id.value + '-modal',
            'label': 'Really kill job?',
            'danger': 'Kill job',
            'button': 'Cancel'
        });
        dialog.appendTo($('#query'));
        dialog.modal();

        $('#daiquiri-modal-danger').click(function() {
            var jobId = $('.daiquiri-modal-dialog').attr('id').split("-")[2];

            $.ajax({
                url: self.url.kill,
                type: 'POST',
                dataType: 'json',
                data: {
                    'id': jobId
                },
                headers: {
                    'Accept': 'application/json'
                },
                error: daiquiri_ajaxError,
                success: function (json){
                    if (json.status == 'ok') {
                        // swich to query tab
                        $('a', self.header.query).tab('show');
                            
                        // hide job tabs
                        self.header.details.hide();
                        self.header.results.hide();
                        self.header.plot.hide();
                        self.header.download.hide();
                            
                        // reload jobs
                        self.displayJobs();
                    } else {
                        daiquiri_jsonError(json);
                    }
                }
            });
        });
    }

    this.renameJob = function (jobId, newTableName, error) {
        var self = this;
        
        // prepare dialog
        $('.daiquiri-modal-dialog').remove();

        var body = '<div><span id="tablename-label">';
        body = '<div><span id="tablename-label">';
        body += '<label for="tablename" class="optional">Table name:</label>';
        body += '</span>';
        body += '<span id="tablename-element">';
        body += '<input class="input-xlarge" type="text" name="tablerename" id="tablerename" value="' + self.job.table.value +'">';
        body += '</span></div>';
        if(typeof error !== 'undefined') {
            body += '<ul class="errors unstyled"><li class="text-error">' + error + '</li></ul>';
        }
        var dialog = self._getModal({
            'id': 'rename-job-' + self.job.id.value + '-modal',
            'label': 'Rename table',
            'body': body,
            'primary': 'Rename table',
            'button': 'Cancel'
        });
        
        dialog.appendTo($('#query'));
        dialog.modal();

        $('#daiquiri-modal-primary').click(function() {
            var jobId = $('.daiquiri-modal-dialog').attr('id').split("-")[2];
            var tablerename = $('input[name="tablerename"]').val()

            $.ajax({
                url: self.url.rename,
                type: 'POST',
                dataType: 'json',
                data: {
                    'id': jobId,
                    'tablerename': tablerename
                },
                headers: {
                    'Accept': 'application/json'
                },
                error: daiquiri_ajaxError,
                success: function (json){
                    if (json.status == 'ok') {
                        // swich to query tab
                        $('a', self.header.query).tab('show');
                            
                        // hide job tabs
                        self.header.details.hide();
                        self.header.results.hide();
                        self.header.plot.hide();
                        self.header.download.hide();
                            
                        // reload jobs
                        self.displayJobs();
                    } else if (json.status == 'error') {
                        errorStr = ""
                        $.each(json.error['tablerename'], function(key, value) {
                            errorStr += value;
                        });

                        self.renameJob(jobId, json.val, errorStr);
                    } else {
                        daiquiri_jsonError(json);
                    }
                }
            });
        });
    }

    this._getStatusIcon = function(status){
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

    this._getModal = function (opt) {
        var html = '';
        if (opt.label != undefined) {
            html += '<div class="modal-header">';
            html += '<h3 id="daiquiri-modal-label">' + opt.label + '</h3>';
            html += '</div>';
        }
        if (opt.body != undefined) {
            html += '<div class="modal-body">' + opt.body + '</div>';
        }
        if (opt.primary != undefined || opt.danger != undefined || opt.button != undefined) {
            html += '<div class="modal-footer">';
            if (opt.primary != undefined) {
                html += '<button id="daiquiri-modal-primary" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">' + opt.primary + '</button>';
            }
            if (opt.danger != undefined) {
                html += '<button id="daiquiri-modal-danger" class="btn btn-danger" data-dismiss="modal" aria-hidden="true">' + opt.danger + '</button>';
            } 
            if (opt.button != undefined) {
                html += '<button id="daiquiri-modal-button" class="btn" data-dismiss="modal" aria-hidden="true">' + opt.button + '</button>';
            }
            html += '</div>';
        }
        return $('<p/>',{
            'aria-hidden': 'true',
            'aria-labelledby': 'daiquiri-modal-label',
            'class' : 'modal hide fade daiquiri-modal-dialog span9',
            'id': opt.id,
            'role': 'dialog',
            'tabindex': '-1',
            'html': html
        })
    }

    /**
     * Formats the successfull retrieval of a download link
     */
    this._getDownloadLink = function (data) { 
        var html = '<p>You can now download the file using:</p>';
        html += '<p><a target="_blank" href="' + data.link + '">';
        html += data.link;
        html += '</a></p>';
        html += '<p>or <a target="_self" id="regenLink" href="javascript: void(0)">regenerate</a> the file.</p>';

        return html;
    }

    this._getDownloadStatus = function(){
        var self = this;

        if (self.downloadPoll.idle == true) {
            $(window).unbind('poll', self.downloadPoll.handler);

            return;
        }

        if (self.downloadPoll.data == '') {
            self.downloadPoll.idle == true;
        }

        if (self.job.status.value != 'success') {
            self.downloadPoll.idle = true;
        } else {
            $.ajax({
                url: self.url.download,
                type: 'POST',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json'
                },
                data: self.downloadPoll.data,
                error: daiquiri_ajaxError,
                success: function (json){
                    var html = '';
                    if (json.status == 'ok') {
                        html = self._getDownloadLink(json);

                        self.downloadPoll.idle = true;

                        $('#daiquiri-query-download-pending').remove();
                        $('#daiquiri-query-download-link').remove();
                        $('<div/>',{
                            'id': 'daiquiri-query-download-link',
                            'html' : html
                        }).appendTo(self.tabs.download);

                        $('#regenLink').click(function(e){
                            e.preventDefault();
                            $.ajax({
                                url: json.regenLink,
                                dataType: 'json',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                error: daiquiri_ajaxError,
                                success: function (json) {
                                    if (json.status == 'ok') {
                                        html = self._getDownloadLink(json);

                                        self.downloadPoll.idle = true;

                                        $('#daiquiri-query-download-pending').remove();
                                        $('#daiquiri-query-download-link').remove();
                                        $('<div/>',{
                                            'id': 'daiquiri-query-download-link',
                                            'html' : html
                                        }).appendTo(self.tabs.download);
                                    } else if (json.status == 'pending') {
                                        $('#daiquiri-query-download-pending').remove();
                                        $('#daiquiri-query-download-link').remove();
                                        $('<div/>',{
                                            'id': 'daiquiri-query-download-pending',
                                            'html' : '<p>Your file is beeing created... Please wait...</p>'
                                        }).appendTo(self.tabs.download);

                                        self.downloadPoll.idle = false;

                                        $(window).bind('poll', _daiquiri_query.downloadPoll.handler());
                                    } else {
                                        self.downloadPoll.idle = true;

                                        daiquiri_jsonError(json);
                                    }
                                }
                            })
                        });
                    } else if (json.status == 'pending') {
                        $('#daiquiri-query-download-pending').remove();
                        $('#daiquiri-query-download-link').remove();
                        $('<div/>',{
                            'id': 'daiquiri-query-download-pending',
                            'html' : '<p>Your file is beeing created... Please wait...</p>'
                        }).appendTo(self.tabs.download);

                        self.downloadPoll.idle = false;

                        $(window).bind('poll', _daiquiri_query.downloadPoll.handler());
                    } else {
                        self.downloadPoll.idle = true;

                        daiquiri_jsonError(json);
                    }
                }
            });
        }
    }
}
