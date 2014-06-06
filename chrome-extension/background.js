window.onbeforeunload = function(e) {
    chrome.extension.sendRequest({
        message: 'clear_sources'
    });
};

$(document).ready(function() {

    /* array for external sources */
    sources = [];
    a = document.createElement('a');
    a.href = location.href;
    sources.push(a.protocol + "//" + a.hostname)


    /* add map to website */
    if ($('body').length > 0) {
        $("body").append("<div id='links-trusting-websites'><div id='sources'>get sources</div></div><div id='map-trusting-websites'></div>");
    } else {
        $("html").append("<body><div id='links-trusting-websites'><div id='sources'>get sources</div></div><div id='map-trusting-websites'></div></body>");
    }
    var map = L.map('map-trusting-websites').setView([52.07265, -4.400929], 2);
    L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    L.control.fullscreen({
        forcePseudoFullscreen: true // force use of pseudo full screen even if full screen API is available, default false
    }).addTo(map);

    /* set image path for leaflet */
    L.Icon.Default.imagePath = chrome.extension.getURL("images");

    /* custom icon markers */
    var MarkerIcon = L.Icon.Default.extend({
        options: {}
    });

    var redIcon = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/marker-icon-red.png"),
        iconSize: [20, 33], // size of the icon
        iconAnchor: [11, 33],
        popupAnchor: [1, -26],
        shadowSize: [33, 33]
    })

    var blueIcon = new MarkerIcon({
        iconSize: [20, 33], // size of the icon
        iconAnchor: [11, 33],
        popupAnchor: [1, -26],
        shadowSize: [33, 33]
    })

    var purpleIcon = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/marker-icon-purple.png"),
        iconSize: [20, 33], // size of the icon
        iconAnchor: [11, 33],
        popupAnchor: [1, -26],
        shadowSize: [33, 33]
    })
    var yellowIcon = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/marker-icon-yellow.png"),
        iconSize: [20, 33], // size of the icon
        iconAnchor: [11, 33],
        popupAnchor: [1, -26],
        shadowSize: [33, 33]
    })
    var greenIcon = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/marker-icon-green.png"),
        iconSize: [20, 33], // size of the icon
        iconAnchor: [11, 33],
        popupAnchor: [1, -26],
        shadowSize: [33, 33]
    })

    var circle = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/circle-icon.png"),
        iconSize: [30, 30], // size of the icon
        shadowSize: [0, 0], // size of the shadow
        iconAnchor: [15, 15], // point of the icon which will correspond to marker's location
        popupAnchor: [0, -10] // point from which the popup should open relative to the iconAnchor
    })

    var circle_extern = new MarkerIcon({
        iconUrl: chrome.extension.getURL("images/circle-icon-extern.png"),
        iconSize: [20, 20], // size of the icon
        shadowSize: [0, 0], // size of the shadow
        iconAnchor: [10, 10], // point of the icon which will correspond to marker's location
        popupAnchor: [0, -7] // point from which the popup should open relative to the iconAnchor
    })

    /* cluster settings */
    var markers = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 10,
        zoomToBoundsOnClick: false
    });

    /* use this offset to identify markers for drawing their radius as circle */
    offset_id = 25;

    server = "";
    chrome.runtime.onMessage.addListener(
        function(request, sender) {
            if (request.message) {
                if (sources.indexOf(request.message) == -1) {
                    $("body").prepend(request.message + "<br>");
                    sources.push(request.message);
                    fetch_url(server, request.message, 1022, true);
                }
                /* send message back so sender knows that message is received */
                chrome.extension.sendRequest({
                    message: request.message
                });
            } else if (request.server) {
                if (server == "") {
                    fetch_url(request.server, location.href);
                }
                server = request.server;
            }
        });

    $('#sources').click(function() {
        get_external_sources();
    })


    function get_external_sources() {
        chrome.extension.sendRequest({
            message: 'request_sources',
            referer: location.href
        });
    }

    chrome.extension.sendRequest({
        message: 'getServer'
    });

    function fetch_url(server, url, code, extern) {

        code = typeof code !== 'undefined' ? code : 1023;
        extern = typeof extern !== 'undefined' ? extern : false;


        /* send url to server and handle result */
        var xhr = new XMLHttpRequest();
        xhr.open("GET", server + "/index.php?url=" + url + "&fields=" + code, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                var resp = JSON.parse(xhr.responseText);
                var extern_message_popup = "";

                /* midpoint */
                if (typeof resp[0].midpoint !== 'undefined') {
                    if (extern) {
                        midpoint = L.marker([resp[0].midpoint.latitude, resp[0].midpoint.longitude], {
                            icon: circle_extern
                        });
                    } else {
                        midpoint = L.marker([resp[0].midpoint.latitude, resp[0].midpoint.longitude], {
                            icon: circle
                        });
                    }
                    midpoint.bindPopup(resp[0].name);
                    markers.addLayer(midpoint);
                }
                if (extern) {
                    extern_message_popup = "extern source: " + resp[0].name + "<br><br>";
                }

                /* user location */
                if (typeof resp[0].user !== 'undefined') {
                    var lat = resp[0].user.coord.latitude;
                    var lon = resp[0].user.coord.longitude;

                    var user = L.marker([lat, lon], {
                        icon: redIcon
                    });

                    var radius = resp[0].user.radius;
                    var user_circle = L.circle([lat, lon], radius * 1000, {
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.25
                    });
                    user.on('popupopen', function(e) {
                        user_circle.addTo(map);
                    });
                    user.on('popupclose', function(e) {
                        map.removeLayer(user_circle)
                    });
                    user.on('mouseover', function(e) {
                        user_circle.addTo(map);
                    });
                    user.on('mouseout', function(e) {
                        if (!e.target.getPopup()._isOpen)
                            map.removeLayer(user_circle)
                    });
                    user.bindPopup("Your location: <br>" + resp[0].user.address);
                    markers.addLayer(user);

                    /* draw line from user to midpoint */
                    if (typeof midpoint !== 'undefined') {
                        var latlngs = Array();
                        latlngs.push(user.getLatLng());
                        latlngs.push(midpoint.getLatLng());
                        add_directed_line(latlngs, map);
                    }
                }

                /* Host locations */
                if (typeof resp[0].hosts !== 'undefined') {
                    var host_circle = {};
                    for (var key in resp[0].hosts) {
                        var host = L.marker([resp[0].hosts[key].coord.latitude, resp[0].hosts[key].coord.longitude], {
                            icon: blueIcon
                        });
                        host._leaflet_id = key + offset_id;
                        markers.addLayer(host);

                        host_circle[key + offset_id] = L.circle([resp[0].hosts[key].coord.latitude, resp[0].hosts[key].coord.longitude], resp[0].hosts[key].radius * 900, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });

                        host.on('popupopen', function(e) {
                            host_circle[e.target._leaflet_id].addTo(map);
                        });
                        host.on('popupclose', function(e) {
                            map.removeLayer(host_circle[e.target._leaflet_id])
                        });

                        host.on('mouseover', function(e) {
                            host_circle[e.target._leaflet_id].addTo(map);
                        });
                        host.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(host_circle[e.target._leaflet_id])
                        });

                        host.bindPopup(extern_message_popup+"Server of " + resp[0].hosts[key].name + " (" + resp[0].hosts[key].ip + "):<br>" + resp[0].hosts[key].address);

                        /* draw line from midpoint to host */
                        if (typeof midpoint !== 'undefined') {
                            var latlngs = Array();
                            latlngs.push(midpoint.getLatLng());
                            latlngs.push(host.getLatLng());
                            add_directed_line(latlngs, map);
                        }

                        if (typeof resp[0].hosts[key].owner !== 'undefined' && typeof resp[0].hosts[key].owner.coord !== 'undefined') {
                            var lat = resp[0].hosts[key].owner.coord.latitude;
                            var lon = resp[0].hosts[key].owner.coord.longitude;

                            var owner = L.marker([lat, lon], {
                                icon: blueIcon
                            });

                            var radius = resp[0].hosts[key].owner.radius;
                            var owner_circle = L.circle([lat, lon], radius * 1000, {
                                color: 'red',
                                fillColor: '#f03',
                                fillOpacity: 0.25
                            });
                            owner.on('popupopen', function(e) {
                                owner_circle.addTo(map);
                            });
                            owner.on('popupclose', function(e) {
                                map.removeLayer(owner_circle)
                            });
                            owner.on('mouseover', function(e) {
                                owner_circle.addTo(map);
                            });
                            owner.on('mouseout', function(e) {
                                if (!e.target.getPopup()._isOpen)
                                    map.removeLayer(owner_circle)
                            });
                            owner.bindPopup("Host company of " + resp[0].hosts[key].ip + ":<br>" + resp[0].hosts[key].owner.name + "<br><br>" + resp[0].hosts[key].owner.address);
                            markers.addLayer(owner);

                            /* draw line from host to owner */
                            var latlngs = Array();
                            latlngs.push(host.getLatLng());
                            latlngs.push(owner.getLatLng());
                            add_directed_line(latlngs, map);
                        }

                    }
                    offset_id = offset_id + 25;
                }

                /* Mail server locations */
                if (typeof resp[0].mservers !== 'undefined') {
                    var mserver_circle = {};
                    for (var key in resp[0].mservers) {
                        var mserver = L.marker([resp[0].mservers[key].coord.latitude, resp[0].mservers[key].coord.longitude], {
                            icon: yellowIcon
                        });
                        mserver._leaflet_id = key + offset_id;
                        markers.addLayer(mserver);

                        mserver_circle[key + offset_id] = L.circle([resp[0].mservers[key].coord.latitude, resp[0].mservers[key].coord.longitude], resp[0].mservers[key].radius * 1000, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });

                        mserver.on('popupopen', function(e) {
                            mserver_circle[e.target._leaflet_id].addTo(map);
                        });
                        mserver.on('popupclose', function(e) {
                            map.removeLayer(mserver_circle[e.target._leaflet_id])
                        });

                        mserver.on('mouseover', function(e) {
                            mserver_circle[e.target._leaflet_id].addTo(map);
                        });
                        mserver.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(mserver_circle[e.target._leaflet_id])
                        });
                        number = +key + 1;
                        mserver.bindPopup(extern_message_popup+"mailserver " + resp[0].mservers[key].name + " (" + resp[0].mservers[key].ip + "):<br>" + resp[0].mservers[key].address);

                        /* draw line from midpoint to mserver */
                        if (typeof midpoint !== 'undefined') {
                            var latlngs = Array();
                            latlngs.push(midpoint.getLatLng());
                            latlngs.push(mserver.getLatLng());
                            add_directed_line(latlngs, map);
                        }
                    }
                    offset_id = offset_id + 25;
                }


                var latlngs = Array();
                if (typeof midpoint !== 'undefined') {
                    latlngs.push(midpoint.getLatLng());
                }

                /* ca's locations */
                if (typeof resp[0].cas !== 'undefined') {
                    var ca_exist = false
                    var ca_circle = {};
                    for (var key in resp[0].cas) {
                        ca_exist = true;
                        var ca = L.marker([resp[0].cas[key].coord.latitude, resp[0].cas[key].coord.longitude], {
                            icon: greenIcon
                        });
                        ca._leaflet_id = key + offset_id;
                        markers.addLayer(ca);

                        ca_circle[key + offset_id] = L.circle([resp[0].cas[key].coord.latitude, resp[0].cas[key].coord.longitude], resp[0].cas[key].radius * 900, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });

                        ca.on('popupopen', function(e) {
                            ca_circle[e.target._leaflet_id].addTo(map);
                        });
                        ca.on('popupclose', function(e) {
                            map.removeLayer(ca_circle[e.target._leaflet_id])
                        });

                        ca.on('mouseover', function(e) {
                            ca_circle[e.target._leaflet_id].addTo(map);
                        });
                        ca.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(ca_circle[e.target._leaflet_id])
                        });

                        /* check if ca is the root ca */
                        if (parseInt(key) + 1 == resp[0].cas.length) var root = "Root ";
                        else var root = "";
                        ca.bindPopup(extern_message_popup+root + "CA location(" + resp[0].cas[key].name + "):<br>" + resp[0].cas[key].address);
                        latlngs.push(ca.getLatLng());
                    }

                    if (ca_exist) {
                        offset_id = offset_id + 25;
                        /* draw line from midpoint to ca's */
                        add_directed_line(latlngs, map);
                    }
                }

                /* domain locations */
                if (typeof resp[0].domain !== 'undefined') {
                    /* domain registrant location */
                    if (typeof resp[0].domain.registrant !== 'undefined' && typeof resp[0].domain.registrant.coord !== 'undefined') {
                        var registrant = L.marker([resp[0].domain.registrant.coord.latitude, resp[0].domain.registrant.coord.longitude], {
                            icon: purpleIcon
                        });
                        registrant.bindPopup(extern_message_popup+"Domain registrant: " + resp[0].domain.registrant.name + "<br><br>location:<br>" + resp[0].domain.registrant.address);
                        markers.addLayer(registrant);

                        var registrant_circle = L.circle([resp[0].domain.registrant.coord.latitude, resp[0].domain.registrant.coord.longitude], resp[0].domain.registrant.radius * 900, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });

                        registrant.on('popupopen', function(e) {
                            registrant_circle.addTo(map);
                        });
                        registrant.on('popupclose', function(e) {
                            map.removeLayer(registrant_circle)
                        });

                        registrant.on('mouseover', function(e) {
                            registrant_circle.addTo(map);
                        });
                        registrant.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(registrant_circle)
                        });
                        if (typeof midpoint !== 'undefined') {
                            var latlngs = Array();
                            latlngs.push(midpoint.getLatLng());
                            latlngs.push(registrant.getLatLng());
                            add_directed_line(latlngs, map);
                        }
                    }

                    /* domain registrar location */
                    if (typeof resp[0].domain.registrar !== 'undefined' && typeof resp[0].domain.registrar.coord !== 'undefined') {
                        var registrar = L.marker([resp[0].domain.registrar.coord.latitude, resp[0].domain.registrar.coord.longitude], {
                            icon: purpleIcon
                        });
                        registrar.bindPopup(extern_message_popup+"Domain registrar: " + resp[0].domain.registrar.name + "<br><br>location:<br>" + resp[0].domain.registrar.address);

                        var lat = resp[0].domain.registrar.coord.latitude;
                        var lon = resp[0].domain.registrar.coord.longitude;
                        var radius = resp[0].domain.registrar.radius;
                        var registrar_circle = L.circle([lat, lon], radius * 900, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });
                        registrar.on('popupopen', function(e) {
                            registrar_circle.addTo(map);
                        });
                        registrar.on('popupclose', function(e) {
                            map.removeLayer(registrar_circle)
                        });
                        registrar.on('mouseover', function(e) {
                            registrar_circle.addTo(map);
                        });
                        registrar.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(registrar_circle)
                        });
                        markers.addLayer(registrar);

                        if (typeof midpoint !== 'undefined') {
                            var latlngs = Array();
                            latlngs.push(midpoint.getLatLng());
                            latlngs.push(registrar.getLatLng());
                            add_directed_line(latlngs, map);
                        }
                    }

                    /* domain tld location */
                    if (typeof resp[0].domain.tld !== 'undefined' && typeof resp[0].domain.tld.coord !== 'undefined') {
                        var tld = L.marker([resp[0].domain.tld.coord.latitude, resp[0].domain.tld.coord.longitude], {
                            icon: purpleIcon
                        });
                        tld.bindPopup(extern_message_popup+"Domain tld: " + resp[0].domain.tld.name + "<br><br>location:<br>" + resp[0].domain.tld.address);

                        var lat = resp[0].domain.tld.coord.latitude;
                        var lon = resp[0].domain.tld.coord.longitude;
                        var radius = resp[0].domain.tld.radius;
                        var tld_circle = L.circle([lat, lon], radius * 900, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.25
                        });
                        tld.on('popupopen', function(e) {
                            tld_circle.addTo(map);
                        });
                        tld.on('popupclose', function(e) {
                            map.removeLayer(tld_circle)
                        });
                        tld.on('mouseover', function(e) {
                            tld_circle.addTo(map);
                        });
                        tld.on('mouseout', function(e) {
                            if (!e.target.getPopup()._isOpen)
                                map.removeLayer(tld_circle)
                        });
                        markers.addLayer(tld);

                        if (typeof midpoint !== 'undefined') {
                            var latlngs = Array();
                            latlngs.push(midpoint.getLatLng());
                            latlngs.push(tld.getLatLng());
                            add_directed_line(latlngs, map);
                        }
                    }
                }


                markers.on('clusterclick', function(a) {
                    a.layer.spiderfy();
                });

                /* add all markers to the map */
                map.addLayer(markers);

                /* set view of the map to fit all markers */
                map.fitBounds(markers.getBounds());
            }
        }
        xhr.send();
    }
});

function add_directed_line(latlngs, map) {
    var polyline = L.polyline(latlngs, {
        color: 'blue'
    }).addTo(map);

    polyline.setText('       ►       ', {
        repeat: true,
        attributes: {
            fill: 'blue'
        }
    });

}