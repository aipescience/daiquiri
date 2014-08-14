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

// based on sampjs.js example file and the stuff the Galform Millenium People did...

// daiquiri namespace
var daiquiri = daiquiri || {};
daiquiri.samp = {};

/**
 * Object to hold the SAMP instance.
 */
daiquiri.samp.item = null;

/**
 * Object to hold the default options of the SAMP object.
 */
daiquiri.samp.opt = {
    client: "Daiquiri-SAMP",
    meta: {
        "samp.name": "Daiquiri-SAMP",
        "samp.description": "SAMP client for the Daiquiri Application",
        "samp.icon.url": null // set in constructor of daiquiri.samp.SAMP
    }
};

/**
 * Object to hold the different names.
 */
daiquiri.samp.names = {
    connectDiv: "-connect",
    connectButton: "-button",
    passwordInput: "-passwordInput",
    clientsList: "-clientsList",
    clientNode: "-clientNode",
    clientIcon: "-clientIcon",
    clientName: "-clientName",
    clientAction: "-clientAction",
    clientPingBtn: "-clientPingBtn",
    clientSendBtn: "-clientSendBtn",
};

/**
 * Constructor-like function for the SAMP class. 
 */
daiquiri.samp.SAMP = function (container, opt) {
    // set state
    this.container = $(container);
    this.id = container.attr('id');
    this.opt = $.extend({},daiquiri.samp.opt, opt);

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
    this.opt.meta["samp.icon.url"] = iconUrl;

    // store object globally, kind of a poor mans singleton.
    daiquiri.samp.item = this;

    this.sampClientTracker = new samp.ClientTracker();
    this.sampClientTracker.parent = this;
    this.sampClientTracker.onchange = function(id, type, data) {
        var parent = this.parent;

        var logString = "";
        logString = "client-id: " + id + " - " + type + "\n"; 
        logString = logString + "data: "  + JSON.stringify(data);

        parent.updateSampClientList();
    };

    var subscriptions = {"*": {}};

    this.sampConnector = new samp.Connector(this.opt.client, this.opt.meta, this.sampClientTracker, subscriptions);
    this.sampConnector.parent = this;
    this.sampConnector.onreg = function() {
        var parent = this.parent;

        $('#' + parent.names.connectButton).html('Unregister from SAMP').button('refresh');

        $('#' + parent.names.connectButton).off("click");
        $('#' + parent.names.connectButton).click(function(e) {
            var id = $(this).attr('id').replace(daiquiri.samp.names.connectButton, "");

            if(daiquiri.samp.item.sampConnector.connection) {
                daiquiri.samp.item.sampConnector.unregister();
            }
        });

        parent.updateSampClientList();

        // bind unload function to window
        window.onbeforeunload = function() {
            if(daiquiri.samp.item.sampConnector.connection) {
                 daiquiri.samp.item.sampConnector.unregister();
            }
        }
    };
    this.sampConnector.onunreg = function() {
        var parent = this.parent;

        $("#" + parent.names.connectButton).html('Register with SAMP').button('refresh');

        $('#' + parent.names.connectButton).off("click");
        $("#" + parent.names.connectButton).click(function(e) {
            var id = $(this).attr('id').replace(daiquiri.samp.names.connectButton, "");

            if(! daiquiri.samp.item.sampConnector.connection) {
                daiquiri.samp.item.sampConnector.register();
            }
        });

        $('#' + parent.names.clientsList).empty();

        parent.updateSampClientList();
    };
    this.sampConnector.register = function() {
        var connector = this;
        var parent = this.parent;

        var regErrHandler = function(err) {
            var target = $("#" + parent.names.clientsList);
            target.empty();

            var webhubUrl = daiquiri.samp.item.opt.baseUrl + '/daiquiri/lib/sampjs/webhub.jnlp';
            target.html("<p>Could not register with a SAMP Hub! You can start one by clicking here:</p>");
            target.append('<p><a href="' + webhubUrl + '" title="Start SAMP Hub using Java web start">Start SAMP Hub using Java web start</a></p>');
        };
        var regSuccessHandler = function(conn) {
            connector.setConnection(conn);
        };
        samp.register(this.name, regSuccessHandler, regErrHandler);
    };

    this.names = {
        id: this.id,
        connectDiv: this.id + daiquiri.samp.names.connectDiv,
        connectButton: this.id + daiquiri.samp.names.connectButton,
        passwordInput: this.id + daiquiri.samp.names.passwordInput,
        clientsList: this.id + daiquiri.samp.names.clientsList,
        clientNode: this.id + daiquiri.samp.names.clientNode,
        clientIcon: this.id + daiquiri.samp.names.clientIcon,
        clientName: this.id + daiquiri.samp.names.clientName,
        clientAction: this.id + daiquiri.samp.names.clientAction,
        clientPingBtn: this.id + daiquiri.samp.names.clientPingBtn,
        clientSendBtn: this.id + daiquiri.samp.names.clientSendBtn,
    };

    this.table = null;
    this.username = null;

    this.displayForm();
};

daiquiri.samp.SAMP.prototype.displayForm = function()  {
    var self = this;

    var div = $('#' + self.names.connectDiv);

    if(div.val() == null) {
        var div = $('<p />', {
            'id': self.names.connectDiv,
            'class': self.names.connectDiv,
            'html': '<button id="' + self.names.connectButton + '" class="linkbutton ' + self.names.connectButton + '">Register with SAMP</button>'
        }).appendTo($("#" + this.id));

        var sampButton = $('#' + self.names.connectButton);
        sampButton.off("click");
        sampButton.click(function(e) {
            var id = $(this).attr('id').replace(daiquiri.samp.names.connectButton, "");

            if(! daiquiri.samp.item.sampConnector.connection) {
                daiquiri.samp.item.sampConnector.register();
            }
        });

        var div = $('<p />', {
            'id': self.names.clientsList,
            'class': self.names.clientsList,
        }).appendTo($("#" + this.id));
    }
    this.updateSampClientList();
};

daiquiri.samp.SAMP.prototype.reset = function() {       
    daiquiri.samp.item.sampConnector.unregister();
};

/**
 * Registers a clientPanelId with the SampCLientsList for updates.
 */
daiquiri.samp.SAMP.prototype.updateSampClientList = function() {
    var clientPanelId = this.names.clientsList;
    var mtype = "samp.app.ping";
    var param = "{}";

    //sending message to hub (basically pinging it)
    var sampMsg = this.createSampMessage(mtype, param);

    this.listSampClients(clientPanelId, sampMsg);
}

/**
 * Creates a SAMP message (json) object by the given args.
 * @param mtype - the SAMP message type (mtype) to create the (SAMP) actions (links) for 
 * @param param - the SAMP parameter for the given mtype, 
 *  together mtype + param form the SAMP message 
 */
daiquiri.samp.SAMP.prototype.createSampMessage = function(mtype, params) {
    if(mtype == null || params == null)
        return null;
    var p2 = eval("(" + params + ")");
    var result = new samp.Message(mtype, p2); 
    this.debugSampMsg("debugSampMsg - result: ", result);
    return result;
};

/**
 * For debugging: Alerts the given SAMP message.
 */
daiquiri.samp.SAMP.prototype.debugSampMsg = function(label, sampMsg) {
    if(true) return;        // debug on|off
    console.log(
        "\n\ttype: " + sampMsg["samp.mtype"] 
        + "\n\tparams: " + sampMsg["samp.params"]
        + "\n\tparams2: " + JSON.stringify(sampMsg["samp.params"])
    );
};

/**
 * Creates/displays the given SAMP-message into the given element.
 * @param targetId - the destined element to add the targeted SAMP actions (links) to 
 * @param sampMsg - the SAMP message to create the (SAMP) actions (links) for 
 */
daiquiri.samp.SAMP.prototype.listSampClients = function(targetId, sampMsg) {
    var self = this;

    var target = $("#" + targetId);

    if(this.isSampConnected()) {
        target.empty();
        target.html("<p>In order for SAMP to work, you need to pass your Daiquiri credentials to SAMP. This needs to be done every time you submit a table to SAMP.</p>");
        target.append("<p>Please enter your password below:</p>");

        var pwdInput = $("<input />", {
            'type': "password",
            'id': self.names.passwordInput,
            'class': self.names.passwordInput,
            'name': self.names.passwordInput
        }).appendTo(target);

        var table = $("<table />", {
            'class': 'table daiquiri-samp-clients',
            'html': '<thead><tr><th></th><th>Client</th><th>Actions</th></tr></thead>'
        }).appendTo(target);

        var tbody = $("<tbody />").appendTo(table);

        var clientIDs = this.isSampConnected() ? this.sampClientTracker.ids : [];
        var node;
        var clientID;
        var info;
        var clientNode;
        var gotClients = false;

        for(clientID in clientIDs) {
            // just for clients with an actual action=subscription! (to the currently targeted sampMsg's samp.mtype)
            if(sampMsg != null) {
                if(!this.isClientSubscribed(clientID, sampMsg)) {
                    continue;
                }
            }

            clientNode = this.createClientNode(clientID, sampMsg);

            if(clientNode == null)
                continue;
            tbody.append(clientNode);
            gotClients = true;
        }

        if(!gotClients) {
            target.html("<p>Could not find any suitable SAMP clients.</p>");
        }
    }
};

/**
 * Determines whether there is a connection to a SAMP hub (established).
 */
daiquiri.samp.SAMP.prototype.isSampConnected = function() {
    return !! this.sampConnector.connection;
};

/** 
 * determines whether the given SAMP client(ID) is/has subscribed (for) the given SAMP mtype 
 * @param clientID - ID of the SAMP client to check
 * @param mtype - the mtype value to check the subscription for 
 */
daiquiri.samp.SAMP.prototype.isClientSubscribed = function(clientID, sampMsg) {
    var result = this.sampClientTracker.subs[clientID] && 
                    samp.isSubscribed(this.sampClientTracker.subs[clientID], sampMsg["samp.mtype"]);
    return result;
};

daiquiri.samp.SAMP.prototype.countReplyHandlers = (function() {
    var count = 0;
    return function() {
        return ++count;
    };
})();

/**
 * Creates the SAMP action links fitting for the given client and the given SAMP message:
 *   "fitting" means that action-links are created if the given client 
 *   actually has subscribed to the given sampMessage.
 * otherwise no accordant action link will be created
 * @param clientID - the ID of the SAMP client to check/create-action-links for/to
 * @param sampMsg - the SAMP message to create the action link for
 * @return a div containing all SAMP action links fitting for the given client and sampMsg (usually just one)  
 */
daiquiri.samp.SAMP.prototype.createClientNode = function(clientID, sampMsg) {
    var self = this;

    if(sampMsg == null)
        return null;

    var clientName = self.sampClientTracker.getName(clientID);
    var clientMeta = self.sampClientTracker.metas[clientID];
    var clientSubs = self.sampClientTracker.subs[clientID];
    var clientIcon = clientMeta ? clientMeta["samp.icon.url"] : null;

    var trClientNode = $('<tr />', {
        'id': self.names.clientNode + "_" + clientID,
        'class': self.names.clientNode
    });

    var tdIcon = $('<td />', {
        'id': self.names.clientIcon + "_" + clientID,
        'class': self.names.clientIcon,
    }).appendTo(trClientNode);

    var imgIcon = $('<img />', {
        'src': clientIcon,
        'class': self.names.clientIcon,
    }).appendTo(tdIcon);

    imgIcon.css("float", "left");
    
    var tdAction = $('<td />', {
        'id': self.names.clientName + "_" + clientID,
        'class': self.names.clientName,
        'html': document.createTextNode(clientName)
    }).appendTo(trClientNode);

    var tdAction = $('<td />', {
        'id': self.names.clientAction + "_" + clientID,
        'class': self.names.clientAction,
    }).appendTo(trClientNode);

    var pingMsg = self.createSampMessage("samp.app.ping", "{}");
    var sendMsg = self.createSampMessage("table.load.votable", '{}');

    if(this.isClientSubscribed(clientID, pingMsg)) {
        var pingButton = $('<button />', {
            'id': self.names.clientPingBtn + "_" + clientID,
            'class': 'linkbutton ' + self.names.clientPingBtn,
            'html': document.createTextNode("Ping " + clientName)
        }).appendTo(tdAction);

        pingButton.off("click");
        pingButton.click(function() {
            var clientID = $(this).attr('id').split("_")[1];
            var id = $(this).attr('id').replace(daiquiri.samp.names.clientPingBtn + "_" + clientID, "");
            var self = daiquiri.samp.item;

            if(self.sampConnector.connection) {
                var tag = self.names.id + self.countReplyHandlers();
                var endCallFunc = function(responderId, msgTag, response) {
                    pingButton.attr("disabled", false);
                    delete self.sampClientTracker.replyHandler[tag];
                };
                pingButton.attr("disabled", true);
                self.sampClientTracker.replyHandler[tag] = endCallFunc;
                self.sampConnector.connection.call([clientID, tag, pingMsg], null, endCallFunc);
            }
        });
    };


    if(self.isClientSubscribed(clientID, sendMsg)) {
        var sendButton = $('<button />', {
            'id': self.names.clientSendBtn + "_" + clientID,
            'class': 'linkbutton ' + self.names.clientSendBtn,
            'html': document.createTextNode("Send Table to " + clientName)
        }).appendTo(tdAction);

        sendButton.off("click");
        sendButton.click(function() {
            var clientID = $(this).attr('id').split("_")[1];
            var id = $(this).attr('id').replace(daiquiri.samp.names.clientSendBtn + "_" + clientID, "");
            var self = daiquiri.samp.item;

            var pwd = $('#' + self.names.passwordInput).val();

            if(!pwd) {
                $('#daiquiri-samp-connect-password-error').remove();
                $('#daiquiri-samp-connect-passwordInput').after('<p id="daiquiri-samp-connect-password-error" class="text-error ">Please give your password.</p>');
                return;
            }

            // construct url
            var s = self.opt.baseStream.split('//');
            var url = s[0] + '//' + self.username + ":" + pwd + "@"
            url += s[1] + "/table/" + encodeURIComponent(self.table) + "/format/votable";
            console.log(url);
            var sendMsg = self.createSampMessage("table.load.votable", '{"url": "' + url + '", "name": "' + self.table + '"}');

            if(self.sampConnector.connection) {
                var tag = self.names.id + self.countReplyHandlers();
                var endCallFunc = function(responderId, msgTag, response) {
                    sendButton.attr("disabled", false);
                    delete self.sampClientTracker.replyHandler[tag];
                };
                sendButton.attr("disabled", true);
                self.sampClientTracker.replyHandler[tag] = endCallFunc;
                self.sampConnector.connection.call([clientID, tag, sendMsg], null, endCallFunc);
            }
        });
    };

    return trClientNode;
};
