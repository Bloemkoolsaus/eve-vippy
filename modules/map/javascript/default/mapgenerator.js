var canvas = null;
var context = null;
var stage = null;
var layer = null;

var totalWidth = 0;
var totalHeight = 0;

var whDefaultWidth = 130;
var whDefaultHeight = 60;
var whDefaultLineHeight = 11;

var massDeleteMode = false;
var blockMapRefresh = false;

var whDragMode = false;
var whOldPosition = { x: 0, y:0 };
var whDrag = { x: 0, y: 0 };
var whMouseOver = false;

var mapWormholes = [];
var mapConnections = [];

function mapRendered()
{
	return (stage != null);
}

function generateMap(data)
{
    if (!allowMapLoadingFinish) {
        console.log("NOT Allow Map Loading Finish!!");
        return false;
    }
    if (data.settings != undefined) {
        whDefaultWidth = data.settings.defaultwidth;
        whDefaultHeight = data.settings.defaultheight;
    }

    totalWidth = $("#mapHeader").innerWidth() - 90;
    totalHeight = 200;

    if (totalWidth > $(window).width())
        totalWidth = $(window).width();

    generateConnections(data.connections);
    generateSystems(data.wormholes);

    if (stage != null)
    {
        console.log("clear stage");
        layer.removeChildren();
        stage.remove(layer);
        stage.removeChildren();
        stage.clear();
    }
    else
    {
        stage = new Kinetic.Stage({
            container: "signatureMap",
            width: totalWidth,
            height: totalHeight,
            id: "sigmap"
        });
    }
    $(window).resize(function() {
        resizeMap();
    });
    resizeMap();

	layer = new Kinetic.Layer();
    for (var c=0; c<mapConnections.length; c++) {
        mapConnections[c].render(layer);
    }
    mapWormholes.forEach(function(wh, key) {
        wh.render(layer);
    });

    // add the layer to the stage
    stage.add(layer);
}

function resizeMap()
{
	var stageWidth = totalWidth + 100;
	var stageHeight = totalHeight + 30;

    stage.setHeight(stageHeight);
    stage.setWidth(stageWidth);

	$("#signatureMap").width(stageWidth);
	$("#signatureMapContainer").width(stageWidth);

	if ($(window).width() > stageWidth) {
		var left = Math.round(($(window).width() - stageWidth) / 2);
		$("#signatureMapContainer").css("left", left);
	} else
		$("#signatureMapContainer").css("left", 0);

	var top = $("#mapHeader").position().top + $("#mapHeader").height() + 14;
	$("#signatureMapContainer").css("top", top);

	$("#signatureMapContainer").height(stageHeight);
	$("#filler").height($("#signatureMapContainer").height()-15);
}

function generateConnections(data)
{
    mapConnections = [];
	for (var i=0; i<data.length; i++)
	{
        var connection = new Connection(data[i].id);

        if (data[i].attributes.mass != null) {      // Mass reduced of crit
            if (data[i].attributes.mass == 1)
                connection.setMassReduced();
            else
                connection.setMassCritical();
        }
        if (data[i].attributes.eol)         // End of Life
            connection.setEndOfLife();
        if (data[i].attributes.normalgates) // Normal gate connectie
            connection.setJumpGates();

        connection.solarsystems.from.system = data[i].from.system;
        connection.solarsystems.from.position.x = data[i].from.position.x;
        connection.solarsystems.from.position.y = data[i].from.position.y;

        connection.solarsystems.to.system = data[i].to.system;
        connection.solarsystems.to.position.x = data[i].to.position.x;
        connection.solarsystems.to.position.y = data[i].to.position.y;


        if (data[i].attributes.capital) {
            if (data[i].attributes.normalgates == null)
                connection.setCapital();
        }
        if (data[i].attributes.frigate) {
            connection.setFrigate();
        }
        if (data[i].attributes.kspacejumps) {
            connection.solarsystems.jumps = data[i].attributes.kspacejumps;
        }

        mapConnections.push(connection);
	}
}

function generateSystems(data)
{
    mapWormholes = [];
	for (var i=0; i<data.length; i++)
	{
        var wormhole = new Wormhole(data[i].id);
        wormhole.setPosition(data[i].position.x, data[i].position.y);

        if (data[i].status != null) {
            wormhole.setStatus(data[i].status);
        }

        if (data[i].persistant != null) {
            if (data[i].persistant)
                wormhole.status.persistant = true;
        }

        if (data[i].whsystem != undefined) {
            wormhole.name = data[i].whsystem.name;
            if (data[i].whsystem.class)
                wormhole.solarsystem.class.tag = data[i].whsystem.class;
            if (data[i].whsystem.effect)
                wormhole.addSubTitle(data[i].whsystem.effect);
            if (data[i].whsystem.statics) {
                for (var s=0; s<data[i].whsystem.statics.length; s++) {
                    wormhole.addStatic(data[i].whsystem.statics[s]);
                }
            }
            if (data[i].whsystem.homesystem)
                wormhole.setAsHomesystem();
        }
        if (data[i].solarsystem != undefined) {
            wormhole.solarsystem.name = data[i].solarsystem.name;
            wormhole.solarsystem.class.color = data[i].solarsystem.class.color;
            if (data[i].solarsystem.class.name == "WH")
                wormhole.solarsystem.type = "wormhole";
            else {
                wormhole.solarsystem.type = data[i].solarsystem.class.name;
                wormhole.solarsystem.class.tag = data[i].solarsystem.class.name;
            }
        }

        if (data[i].fullyscanned != undefined) {
            wormhole.scanned.scanned = true;
            if (data[i].fullyscanned <= 0)
                wormhole.scanned.finished = true;
        }

        if (wormhole.isKspace()) {
            wormhole.addTitle(data[i].solarsystem.region);
            if (data[i].tradehub != null && data[i].tradehub.jumps != null)
                wormhole.addSubTitle(data[i].tradehub.jumps + " jumps to " + data[i].tradehub.name);
        }

        if (data[i].whsystem.titles != undefined) {
            for (var t=0; t<data[i].whsystem.titles.length; t++) {
                wormhole.addTitle(data[i].whsystem.titles[t].name, data[i].whsystem.titles[t].color)
            }
        }

        if (data[i].attributes != null)
        {
            if (data[i].attributes.factionid != null)
                wormhole.solarsystem.faction = data[i].attributes.factionid;

            if (data[i].attributes.stations != null)            // Station system?
                wormhole.addIcon(mapIcons.station);
            if (data[i].attributes.cyno != null)                // Caps in range?
                wormhole.addIcon(mapIcons.station);
            if (data[i].attributes.hsisland != null)            // HS-Island?
                wormhole.addIcon(mapIcons.hsisland);
            if (data[i].attributes.direcths != null)            // Direct-HS
                wormhole.addIcon(mapIcons.direcths);

            // Contested / faction warfare
            if (data[i].attributes.contested != null)
                wormhole.addIcon(mapIcons.contested);
            else if (data[i].attributes.fwsystem)
                wormhole.addIcon(mapIcons.fw);
        }

        // Recent kills
        if (data[i].whsystem.class && data[i].kills != null) {
            if (data[i].kills.pvp > 0)
                wormhole.addIcon(mapIcons.pvp);
            if (data[i].kills.pve > 0)
                wormhole.addIcon(mapIcons.pve);
        }

        // Toons
        if (data[i].characters != null) {
            for (var c=0; c<data[i].characters.length; c++) {
                wormhole.addCharacter(data[i].characters[c].id, data[i].characters[c].name);
            }
        }

        mapWormholes[wormhole.id] = wormhole;
	}
}

function openContextMenu(whName, mouseX, mouseY)
{
	$("#maincontent").append("<div id='disabledPageTransparent'></div>");
	$("#disabledPageTransparent").css("height",$(document).height());
	$("#disabledPageTransparent").show();
	$("#disabledPageTransparent").click(function() { closeContextMenu(); });

	var contextContainer = "<div id='wormholeContext' style='top: "+mousePosY+"px; left: "+mousePosX+"px;'>";
	contextContainer += "<div id='wormholeContextHeader'>&nbsp;</div>";
	contextContainer += "<div id='wormholeContextMenu'><div style='padding: 10px;'><img src='/images/loading.gif'> &nbsp; Loading context menu</div></div>";
	contextContainer += "<div id='wormholeContextFooter'>&nbsp;</div>";
	contextContainer += "</div>";
	$("#maincontent").append(contextContainer);

	$.ajax({
		url: "/map/system/context/"+whName,
        data: { ajax: 1 },
		success: function(data) {
			$("#wormholeContextMenu").html(data);
		}
	});
	return false;
}
function closeContextMenu()
{
	$("#disabledPageTransparent").remove();
	$("#wormholeContext").fadeOut("fast", $("#wormholeContext").remove());
}

function mapIsMassDeleteMode()
{
	return massDeleteMode;
}
function mapSetMassDelete()
{
	massDeleteMode = true;
}
function mapUnSetMassDelete()
{
	massDeleteMode = false;
}

var popupActive = false;
function openWormholeDetails(system,x,y)
{
	if (isContextOpen())
		return false;

    disableMapRefresh();

	var className = "";
	var posLeft = x + whDefaultWidth + $("#signatureMap").position().left;
	var posTop = y + $("#signatureMap").position().top - 20;

	if (($(window).width()-posLeft) < 380) {
		posLeft = posLeft - whDefaultWidth - 400;
		className += "right";
	} else
		className += "left";

	var html = "<div class='tooltip' id='whInfo"+system+"'>";
	html += "<div id='whInfo"+system+"Header' class='header"+className+"'></div>";
	html += "<div id='whInfo"+system+"Details' class='content"+className+"'>";
	html += "<img src='/images/loading.gif'> &nbsp; Loading system data";
	html += "</div>";
	html += "<div id='whInfo"+system+"Footer' class='footer"+className+"'></div>";
	html += "</div>";

	$("#signatureMapContainer").append(html);
	$("#whInfo"+system).css("position","absolute");
	$("#whInfo"+system).css("left", posLeft);
	$("#whInfo"+system).css("top", posTop);
	$("#whInfo"+system).fadeIn();

    $.ajax({
        url: "/map/system/details/"+system,
        data: {
            ajax: 1
        },
        success: function(data) {
            if (isContextOpen())
                return false;

            $("#whInfo"+system+"Details").html(data);
            fetchSystemTradeHubs(system);
            fetchWormholeDetailsActivity(system);
        }
    });
}
function closeWormholeDetails(system)
{
    if ($("#whInfo"+system).length > 0) {
        $("#whInfo"+system).remove();
        enableMapRefresh();
    }
}

function fetchSystemTradeHubs(system)
{
    if (isContextOpen())
        return false;

    if ($("#whinfotradehubs").length == 0)
        return false;

    $.ajax({
        url: "/map/system/tradehubs/"+system,
        data: {
            ajax: 1
        },
        success: function(data) {
            if (!isContextOpen())
                $("#whinfotradehubs").html(data);
        }
    });
}
function fetchWormholeDetailsActivity(system)
{
	if (isContextOpen())
		return false;

    if ($("#whInfoActivity").length == 0)
        return false;

	$.ajax({
		url: "/map/system/activity/"+system,
		data: {
			ajax: 1
		},
		success: function(data) {
			if (!isContextOpen()) {
                data = $.parseJSON(data);
                $("#whActivityDate").html(data.date);
                $("#whInfoActivity").html("<img src='/"+data.url+"'/>");
            }
		}
	});
}

function openConnectionDetails(connectionID, x, y)
{
    disableMapRefresh();
	var className = "";
	var posLeft = mousePosX+15;
	var posTop = mousePosY-50;

	if (($(window).width()-posLeft) < 380)
		className += "right";
	else
		className += "left";

	var popupID = "connInfo"+connectionID;
	var html = "<div class='tooltip' id='"+popupID+"'>";
	html += "<div class='header"+className+"'></div>";
	html += "<div class='content"+className+"' id='conndetailsinfo' style='padding-left: 30px;'>";
	html += "<img src='/images/loading.gif'> &nbsp; Loading wormhole data";
	html += "</div>";
	html += "<div class='footer"+className+"'></div>";
	html += "</div>";

	$("#maincontainer").append(html);

	if (className == "right")
		posLeft = posLeft - 20 - $("#"+popupID).width();

	$("#"+popupID).css("position","absolute");
	$("#"+popupID).css("left", posLeft);
	$("#"+popupID).css("top", posTop);
	$("#"+popupID).fadeIn();
	$("#connInfo"+connectionID).fadeIn();

    $.ajax({
        url: "/map/connection/details/"+connectionID,
        data: { ajax: 1 },
        success: function(data) {
            $("#conndetailsinfo").html(data);
            if ($("#jumplogsummary").length > 0) {
                // Jumplog halen
                $("#jumplogsummary").html("<img src='/images/loading.gif'> &nbsp; Loading jump log");
                $.ajax({
                    url: "/map/connection/jumplog/" + connectionID,
                    data: {ajax: 1},
                    success: function (data) {
                        $("#jumplogsummary").html(data);
                    }
                });
            }
        }
    });
}
function closeConnectionDetails(who)
{
    enableMapRefresh();
	var popupID = "connInfo"+who.replace(",","-");
	$("#"+popupID).remove();
}

function isContextOpen()
{
	if ($("#wormholeContext").length > 0)
		return true;
	else
		return false;
}
