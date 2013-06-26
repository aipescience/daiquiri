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

 //based on sampjs.js example file and the stuff the Galform Millenium People did...

var _daiquiri_samp = {
    defaults: {
        client: "Daiquiri-SAMP",
        meta: {
            "samp.name": "Daiquiri-SAMP",
            "samp.description": "SAMP client for the Daiquiri Application",
            "samp.icon.url": "http://escience.aip.de/daiquiri/favicon.ico"
        }
    },
    names: {
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
    },
    items: {}
}

function Daiquiri_Samp(container, opt, id) {
    // set state
    this.container = container;
    this.opt = opt;

    var tmp = window.location.href.toString().match("(^(?:(?:.*?)?//)?[^/?#;]*)");
    this.domainUrl = tmp[1].replace("http://", "").replace("https://", "");

    this.sampClientTracker = new samp.ClientTracker();
    this.sampClientTracker.parent = this;
    var subscriptions = {"*": {}};

    this.sampConnector = new samp.Connector(this.opt.client, this.opt.meta, this.sampClientTracker, subscriptions);
    this.sampConnector.parent = this;

    this.names = {
        id: id,
        connectDiv: id + _daiquiri_samp.names.connectDiv,
        connectButton: id + _daiquiri_samp.names.connectButton,
        passwordInput: id + _daiquiri_samp.names.passwordInput,
        clientsList: id + _daiquiri_samp.names.clientsList,
        clientNode: id + _daiquiri_samp.names.clientNode,
        clientIcon: id + _daiquiri_samp.names.clientIcon,
        clientName: id + _daiquiri_samp.names.clientName,
        clientAction: id + _daiquiri_samp.names.clientAction,
        clientPingBtn: id + _daiquiri_samp.names.clientPingBtn,
        clientSendBtn: id + _daiquiri_samp.names.clientSendBtn,
    };

    this.tableName = null;
    this.userName = null;

    /***
     * Functions managing display and behaviour
     ***/
    this.displayForm = function(id)  {
        var self = this;

        var div = $('#' + self.names.connectDiv);

        if(div.val() == null) {
            var div = $('<div />', {
                'id': self.names.connectDiv,
                'class': self.names.connectDiv,
                'html': '<button id="' + self.names.connectButton + '" class="' + self.names.connectButton + '">Register with SAMP</button>'
            }).appendTo($("#" + id));

            var sampButton = $('#' + self.names.connectButton);
            sampButton.off("click");
            sampButton.click(function(e) {
                var id = $(this).attr('id').replace(_daiquiri_samp.names.connectButton, "");

                if(! _daiquiri_samp.items[id].sampConnector.connection) {
                    _daiquiri_samp.items[id].sampConnector.register();
                }
            });

            var div = $('<div />', {
                'id': self.names.clientsList,
                'class': self.names.clientsList,
            }).appendTo($("#" + id));

            //bind unload function to window
            $(window).off('beforeunload');
            $(window).on('beforeunload', function() {
                for(connectionID in _daiquiri_samp.items) {
                    if(_daiquiri_samp.items[id].sampConnector.connection) {
                        _daiquiri_samp.items[id].sampConnector.unregister();
                    }
                }
            });
        }
        this.updateSampClientList();
    };

    this.reset = function() {       
        _daiquiri_samp.items[id].sampConnector.unregister();
    };


    /***
     * SAMP stuff
     ***/
    this.sampClientTracker.onchange = function(id, type, data) {
        var parent = this.parent;

        var logString = "";
        logString = "client-id: " + id + " - " + type + "\n"; 
        logString = logString + "data: "  + JSON.stringify(data);

        parent.updateSampClientList();
    };

    this.sampConnector.onreg = function() {
        var parent = this.parent;

        $('#' + parent.names.connectButton).html('Unregister from SAMP').button('refresh');

        $('#' + parent.names.connectButton).off("click");
        $('#' + parent.names.connectButton).click(function(e) {
            var id = $(this).attr('id').replace(_daiquiri_samp.names.connectButton, "");

            if(_daiquiri_samp.items[id].sampConnector.connection) {
                _daiquiri_samp.items[id].sampConnector.unregister();
            }
        });

        parent.updateSampClientList();
    };

    this.sampConnector.onunreg = function() {
        var parent = this.parent;

        $("#" + parent.names.connectButton).html('Register with SAMP').button('refresh');

        $('#' + parent.names.connectButton).off("click");
        $("#" + parent.names.connectButton).click(function(e) {
            var id = $(this).attr('id').replace(_daiquiri_samp.names.connectButton, "");

            if(! _daiquiri_samp.items[id].sampConnector.connection) {
                _daiquiri_samp.items[id].sampConnector.register();
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
            target.html("<p>Could not register with a SAMP Hub! You can start one by clicking here:</p>");
            target.append('<p><a href="http://www.star.bristol.ac.uk/~mbt/websamp/webhub.jnlp" title="Start SAMP Hub using Java web start">Start SAMP Hub using Java web start</a></p>');
        };
        var regSuccessHandler = function(conn) {
            connector.setConnection(conn);
        };
        samp.register(this.name, regSuccessHandler, regErrHandler);
    };


    //getter and setter:
    this.setTable = function(tableName) {
        this.tableName = tableName;
    }

    this.setUser = function(userName) {
        this.userName = userName;
    }

    /**
     * registeres a clientPanelId with the SampCLientsList for updates
     * @param elemID - the targeted element's ID
     */
    this.updateSampClientList = function() {
        var clientPanelId = this.names.clientsList;
        var mtype = "samp.app.ping";
        var param = "{}";

        //sending message to hub (basically pinging it)
        var sampMsg = this.createSampMessage(mtype, param);

        this.listSampClients(clientPanelId, sampMsg);
    }

    /**
     * create a SAMP message (json) object by the given args
     * @param mtype - the SAMP message type (mtype) to create the (SAMP) actions (links) for 
     * @param param - the SAMP parameter for the given mtype, 
     *  together mtype + param form the SAMP message 
     */
    this.createSampMessage = function(mtype, params) {
        if(mtype == null || params == null)
            return null;
        var p2 = eval("(" + params + ")");
        var result = new samp.Message(mtype, p2); 
        this.debugSampMsg("debugSampMsg - result: ", result);
        return result;
    }
    /**
     * just for debugging: alerts the given SAMP message 
     */
    this.debugSampMsg = function(label, sampMsg) {
        if(true) return;        // debug on|off
        console.log(
            "\n\ttype: " + sampMsg["samp.mtype"] 
            + "\n\tparams: " + sampMsg["samp.params"]
            + "\n\tparams2: " + JSON.stringify(sampMsg["samp.params"])
        );
    }

    /**
     * creates/displays the given SAMP-message into the given element 
     * @param targetId - the destined element to add the targeted SAMP actions (links) to 
     * @param sampMsg - the SAMP message to create the (SAMP) actions (links) for 
     */
    this.listSampClients = function(targetId, sampMsg) {
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

            target.append("<p>Clients:</p>");

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
                target.append(clientNode);
                gotClients = true;
            }

            if(!gotClients) {
                target.html("<p>Could not find any suitable SAMP clients.</p>");
            }
        }
    };

    /**
     * determines whether there is a connection to a SAMP hub (established)
     */
    this.isSampConnected = function() {
        return !! this.sampConnector.connection;
    };

    /** 
     * determines whether the given SAMP client(ID) is/has subscribed (for) the given SAMP mtype 
     * @param clientID - ID of the SAMP client to check
     * @param mtype - the mtype value to check the subscription for 
     */
    this.isClientSubscribed = function(clientID, sampMsg) {
        var result = this.sampClientTracker.subs[clientID] && 
                        samp.isSubscribed(this.sampClientTracker.subs[clientID], sampMsg["samp.mtype"]);

        return result;
    };

    this.countReplyHandlers = (function() {
        var count = 0;
        return function() {
            return ++count;
        };
    })();

    /**
     * creates the SAMP action links fitting for the given client and the given SAMP message:
     *   "fitting" means that action-links are created if the given client 
     *   actually has subscribed to the given sampMessage.
     * otherwise no accordant action link will be created
     * @param clientID - the ID of the SAMP client to check/create-action-links for/to
     * @param sampMsg - the SAMP message to create the action link for
     * @return a div containing all SAMP action links fitting for the given client and sampMsg (usually just one)  
     */
    this.createClientNode = function(clientID, sampMsg) {
        var self = this;

        if(sampMsg == null)
            return null;

        var clientName = self.sampClientTracker.getName(clientID);
        var clientMeta = self.sampClientTracker.metas[clientID];
        var clientSubs = self.sampClientTracker.subs[clientID];
        var clientIcon = clientMeta ? clientMeta["samp.icon.url"] : null;

        var divClientNode = $('<div />', {
            'id': self.names.clientNode + "_" + clientID,
            'class': self.names.clientNode
        });

        var spanIcon = $('<span />', {
            'id': self.names.clientIcon + "_" + clientID,
            'class': self.names.clientIcon,
        }).appendTo(divClientNode);

        var imgIcon = $('<img />', {
            'src': clientIcon,
            'width': 24,
            'class': self.names.clientIcon,
        }).appendTo(spanIcon);

        imgIcon.css("float", "left");
        
        var nameSpan = $('<span />', {
            'id': self.names.clientName + "_" + clientID,
            'class': self.names.clientName,
            'html': document.createTextNode(clientName)
        }).appendTo(divClientNode);

        var actionSpan = $('<span />', {
            'id': self.names.clientAction + "_" + clientID,
            'class': self.names.clientAction,
        }).appendTo(divClientNode);

        var pingMsg = self.createSampMessage("samp.app.ping", "{}");
        var sendMsg = self.createSampMessage("table.load.votable", '{}');

        if(this.isClientSubscribed(clientID, pingMsg)) {
            var pingButton = $('<button />', {
                'id': self.names.clientPingBtn + "_" + clientID,
                'class': self.names.clientPingBtn,
                'html': document.createTextNode("Ping " + clientName)
            }).appendTo(actionSpan);

            pingButton.off("click");
            pingButton.click(function() {
                var clientID = $(this).attr('id').split("_")[1];
                var id = $(this).attr('id').replace(_daiquiri_samp.names.clientPingBtn + "_" + clientID, "");
                var parent = _daiquiri_samp.items[id];

                if(parent.sampConnector.connection) {
                    var tag = parent.names.id + parent.countReplyHandlers();
                    var endCallFunc = function(responderId, msgTag, response) {
                        pingButton.attr("disabled", false);
                        delete parent.sampClientTracker.replyHandler[tag];
                    };
                    pingButton.attr("disabled", true);
                    parent.sampClientTracker.replyHandler[tag] = endCallFunc;
                    parent.sampConnector.connection.call([clientID, tag, pingMsg], null, endCallFunc);
                }
            });
        };


        if(self.isClientSubscribed(clientID, sendMsg)) {
            var sendButton = $('<button />', {
                'id': self.names.clientSendBtn + "_" + clientID,
                'class': self.names.clientSendBtn,
                'html': document.createTextNode("Send Table to " + clientName)
            }).appendTo(actionSpan);

            sendButton.off("click");
            sendButton.click(function() {
                var clientID = $(this).attr('id').split("_")[1];
                var id = $(this).attr('id').replace(_daiquiri_samp.names.clientSendBtn + "_" + clientID, "");
                var parent = _daiquiri_samp.items[id];

                var pwd = $('#' + parent.names.passwordInput).val();

                if(!pwd) {
                    return;
                }

                //construct url
                var url = "";
                if(parent.opt.streamHttps == false) {
                    url = "http://";
                } else {
                    url = "https://";
                }

                url = url + parent.userName + ":" + pwd + "@" + parent.domainUrl + "/" + parent.opt.baseUrl;
                url = url + parent.opt.baseStream + "/table/" + encodeURIComponent(parent.tableName) + "/format/votable";

                var sendMsg = parent.createSampMessage("table.load.votable", '{"url": "' + url + '", "name": "' + parent.tableName + '"}');

                if(parent.sampConnector.connection) {
                    var tag = parent.names.id + parent.countReplyHandlers();
                    var endCallFunc = function(responderId, msgTag, response) {
                        sendButton.attr("disabled", false);
                        delete parent.sampClientTracker.replyHandler[tag];
                    };
                    sendButton.attr("disabled", true);
                    parent.sampClientTracker.replyHandler[tag] = endCallFunc;
                    parent.sampConnector.connection.call([clientID, tag, sendMsg], null, endCallFunc);
                }
            });
        };

        return divClientNode;
    };
};

// in the document, you'd write, at an appropriate place:
// <button id="sendViaSAMP" title="Broadcasts this table to all 
// SAMP clients on your desktop.
// This needs a fairly modern hub to work.">Send via SAMP</button>
// (or whatever else you fancy).

// main plugin
(function($){
    $.fn.extend({ 
        daiquiri_samp: function(opt) {
            
            // apply default options
            opt = $.extend(_daiquiri_samp.defaults, opt);

            return this.each(function() {
                var id = $(this).attr('id');

                // check if plot is already set
                if (_daiquiri_samp.items[id] == undefined) {
                    _daiquiri_samp.items[id] = new Daiquiri_Samp($(this),opt,id);
                } else {
                    _daiquiri_samp.items[id].reset();
                    _daiquiri_samp.items[id].opt = opt;
                }
                
                _daiquiri_samp.items[id].displayForm(id);
            });  
        },
        daiquiri_samp_setTable: function(tableName) {
            var id = $(this).attr('id');

            if (_daiquiri_samp.items[id] != undefined) {
                _daiquiri_samp.items[id].setTable(tableName);
            }
        },
        daiquiri_samp_setUser: function(userName) {
            var id = $(this).attr('id');

            if (_daiquiri_samp.items[id] != undefined) {
                _daiquiri_samp.items[id].setUser(userName);
            }
        }
    });
})(jQuery);
