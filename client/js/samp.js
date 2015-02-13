/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

angular.module('samp', [])

.factory('SampService', ['$timeout','$window','$location','$http','$cookies',function($timeout,$window,$location,$http,$cookies) {

    var clients = {};
    var errors = {};

    var options = {
        client: "Daiquiri-SAMP",
        meta: {
            "samp.name": "Daiquiri-SAMP",
            "samp.description": "SAMP client for the Daiquiri Application",
            "samp.icon.url": getIcon()
        }
    }

    var info = false;

    var sampClientTracker;
    var subscriptions;
    var sampConnector;

    var base = angular.element('base').attr('href');
    var absBase = $location.absUrl().split('/query')[0];

    function getIcon() {
        // try to get favicon
        var icon = $('link[rel="icon"]');
        var iconUrl;
        if (icon.length != 0) {
            iconUrl = icon.attr('href');
        } else {
            icon = $('link[rel="shortcut icon"]');
            if (icon.length != 0) {
                iconUrl = icon.attr('href');
            } else {
                console.log('Error: Favicon not found.');
            }
        }
        if (iconUrl.toLowerCase().indexOf('http://') !== 0 && iconUrl.toLowerCase().indexOf('https://') !== 0) {
            iconUrl = window.location.origin + '/' + iconUrl;
        }
        return iconUrl;
    }

    function register() {
        // clean up errors
        for (var error in errors) delete errors[error];

        // init client tracker
        sampClientTracker = new samp.ClientTracker();

        // register a callback to update the client list when the client tracker find a new client
        sampClientTracker.onchange = function(id, type, data) {
            updateClients(id);
        };

        // init subscription
        subscriptions = {"*": {}};

        // init samp connector
        sampConnector = new samp.Connector(options.client, options.meta, sampClientTracker, subscriptions);

        // register samp service
        samp.register('hey', function(connection) {
            $timeout(function() {
                // a connection could be established
                sampConnector.setConnection(connection);

                // bind unload function to window
                $window.onbeforeunload = function() {
                    if (sampConnector.connection) sampConnector.unregister();
                }
            });
        }, function(error) {
            $timeout(function() {
                // an error occured
                if (error.toString() === 'No hub?') {
                    errors.connection = ['No SAMP hub was found. Maybe TOPCAT or Aladin has not been started yet.'];
                } else {
                    errors.connection = ['An error occured (' + error.toString() + ').'];
                }
            });
        });
    };

    function unregister() {
        $timeout(function() {
            sampConnector.unregister();
        });
    }

    function ping(id) {
        var message = new samp.Message('samp.app.ping',{});
        var tag = Math.random().toString(36).substring(7);
        var callback = function(responderId, msgTag, response) {
            $timeout(function () {
                if (response['samp.status'] == "samp.ok") {
                    info = {'tag': tag, 'text': clients[id].name + ' was successfully pinged.'};
                } else {
                    info = {'tag': tag, 'text': 'An error occured.'};
                    console.log(response);
                }
            });

            delete sampClientTracker.replyHandler[tag];
        }
        sampClientTracker.replyHandler[tag] = callback;
        sampConnector.connection.call([id, tag, message], null, callback);
    }

    function send(id, table, username, password) {
        // get path
        var path = '/query/download/stream/format/votable/table/' + encodeURIComponent(table);

        var data = {
            'csrf': $cookies['XSRF-TOKEN'],
            'path': path
        };

        // get an auth token
        $http.post(base + '/auth/token/create', $.param(data))
            .success(function(response) {
                // // construct url
                var s = absBase.split('//');
                var url = s[0] + '//' + username + ":" + response.token + "@" + s[1] + path;

                var message = new samp.Message('table.load.votable',{'url': url, 'name' : table});
                var tag = Math.random().toString(36).substring(7);
                var callback = function(responderId, msgTag, response) {
                    $timeout(function () {
                        if (response['samp.status'] == "samp.ok") {
                            info = {'tag': tag, 'text': 'The table was successfully transfered to ' + clients[id].name + '.'};
                        } else {
                            info = {'tag': tag, 'text': 'An error occured.'};
                            console.log(response);
                        }
                    });

                    delete sampClientTracker.replyHandler[tag];
                }
                sampClientTracker.replyHandler[tag] = callback;
                sampConnector.connection.call([id, tag, message], null, callback);

            })
            .error(function (response,status) {

            });
    }

    function updateClients(id) {
        $timeout(function() {

            var name = sampClientTracker.getName(id);
            var meta = sampClientTracker.metas[id];

            var subs = {};

            if (angular.isUndefined(meta)) {
                delete clients[id];
            } else {
                angular.forEach(sampClientTracker.subs[id], function(sub, key) {
                    if (key === 'samp.app.ping') {
                        subs.ping = true;
                    } else if (key === 'table.load.votable') {
                        subs.send = true;
                    }
                });
                clients[id] = {
                    id: id,
                    icon: meta ? meta["samp.icon.url"] : null,
                    name: name,
                    subs: subs
                };
            }
        });
    }

    function isConnected() {
        if (angular.isUndefined(sampConnector)) {
            return false;
        } else {
            return !! sampConnector.connection;
        }
    };

    function getInfo() {
        return info;
    }

    return {
        errors: errors,
        clients: clients,
        register: register,
        unregister: unregister,
        ping: ping,
        send: send,
        isConnected: isConnected,
        getInfo: getInfo
    };
}])

