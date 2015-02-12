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

.factory('SampService', ['$timeout','$window',function($timeout,$window) {

    clients = {};
    errors = {};

    var options = {
        client: "Daiquiri-SAMP",
        meta: {
            "samp.name": "Daiquiri-SAMP",
            "samp.description": "SAMP client for the Daiquiri Application",
            "samp.icon.url": getIcon()
        }
    }

    var sampClientTracker;
    var subscriptions;
    var sampConnector;

    /**
     * Determines the url of the favicon.
     * @return string icon
     */
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

    /**
     * Registers with the SAMP hub.
     */
    function register() {
        // clean up errors
        for (var error in samp.errors) delete samp.errors[error];

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

    /**
     * Unregisters from the SAMP hub.
     */
    function unregister() {
        $timeout(function() {
            sampConnector.unregister();
        });
    }

    function ping(id) {
        console.log('ping ' + id);
    }

    function send(id) {
        console.log('send ' + id);
    }

    /**
     * Updates the clients object.
     * @param  id  the id of the SAMP client
     */
    function updateClients(id) {
        $timeout(function() {

            var name = sampClientTracker.getName(id);
            var meta = sampClientTracker.metas[id];

            var subs = {};
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
        });
    }

    /**
     * Creates a SAMP message (json) object by the given args.
     * @param  mtype  the SAMP message type (mtype) to create the (SAMP) actions (links) for
     * @param  param  the SAMP parameter for the given mtype,
     */
    function createSampMessage(mtype, params) {
        if(mtype == null || params == null) return null;

        var p2 = eval("(" + params + ")");
        var result = new samp.Message(mtype, p2);
        return result;
    };

    /**
     * Returns whether there is a connection to a SAMP hub established.
     */
    function isConnected() {
        if (angular.isUndefined(sampConnector)) {
            return false;
        } else {
            return !! sampConnector.connection;
        }
    };

    return {
        errors: errors,
        clients: clients,
        register: register,
        unregister: unregister,
        ping: ping,
        send: send,
        isConnected: isConnected
    };
}])

