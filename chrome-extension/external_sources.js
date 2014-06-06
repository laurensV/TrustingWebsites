queue = {};
exceptions = ["https://github.com/brunob/leaflet.fullscreen/raw/master/icon-fullscreen.png",
    "http://b.tile.osm.org",
    "http://a.tile.osm.org",
    "http://c.tile.osm.org",
    "https://raw.githubusercontent.com/brunob/leaflet.fullscreen/master/icon-fullscreen.png",
    "http://iavconcepts.com"
];


chrome.extension.onRequest.addListener(function(request, sender) {
    if (request.message == 'request_sources') {
        chrome.webRequest.handlerBehaviorChanged();
        if (sender.tab.id in queue) {
            for (i in queue[sender.tab.id]) {
                chrome.tabs.sendMessage(sender.tab.id, {
                    message: queue[sender.tab.id][i]
                });
                //queue[sender.tab.id].splice(i, 1);
                delete queue[sender.tab.id][i];
            }
        }
    } else if (request.message == 'getServer') {
        chrome.storage.sync.get({
            /* default server */
            server: 'http://iavconcepts.com/test',
        }, function(items) {
            chrome.tabs.sendMessage(sender.tab.id, {
                server: items.server
            });
        });
    } else if(request.message == 'clear_sources'){
        queue[sender.tab.id] = [];
        console.log("CLEAR");
    } else if(request.message == 'clear_sources2'){
        queue[sender.tab.id] = [];
        console.log("CLEAR2!!!");
    }
});

chrome.webRequest.onBeforeSendHeaders.addListener(function(details) {
    var a = document.createElement('a');
    a.href = details.url;
    console.log(a.protocol + "//" + a.hostname)
    console.log(details.requestHeaders);
    /*
    var referer;
    for (var i in details.requestHeaders) {
        if (details.requestHeaders[i].name == "Referer") {
            referer = details.requestHeaders[i].value;
            break;
        }
    }
    */

    if (exceptions.indexOf(details.url) == -1 && exceptions.indexOf(a.protocol + "//" + a.hostname) == -1) {
        if (!(details.tabId in queue)) {
            queue[details.tabId] = [];
        }
        if (queue[details.tabId].indexOf(a.protocol + "//" + a.hostname) == -1) {
            queue[details.tabId].push(a.protocol + "//" + a.hostname);
        }
    }

}, {
    urls: ["<all_urls>"]
}, ['requestHeaders']);