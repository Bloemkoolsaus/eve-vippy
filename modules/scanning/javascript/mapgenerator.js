var canvas = null;
var context = null;
var stage = null;
var layer = null;
var mapZoom = 100;

var x1 = 0;
var y1 = 0;
var x2 = 0;
var y2 = 0;

var maxWidth = 0;
var maxHeight = 0;

var totalWidth = 0;
var totalHeight = 0;

var whDefaultWidth = 130;
var whDefaultHeight = 60;
var whDefaultLineHeight = 11;

var massDeleteMode = false;
var blockMapRefresh = false;

var whDragX = 0;
var whDragY = 0;


function mapRendered()
{
	if (stage == null)
		return false;
	else
		return true;
}

function mapZoomIn()
{
	mapZoom += 10;
	$("#mapZoom").attr("value",mapZoom);
}
function mapZoomOut()
{
	mapZoom -= 10;
	if (mapZoom < 50)
		mapZoom = 50;
	$("#mapZoom").attr("value",mapZoom);
}

function generateMap(data)
{
	if (blockMapRefresh)
		return true;

	if (!mapRendered())
	{
		maxWidth = $("#maincontainer").width()-30;
		maxHeight = $("#signatureMap").height()-20;

	    stage = new Kinetic.Stage({
	        container: "signatureMap",
	        width: maxWidth,
	        height: maxHeight,
	        id: "sigmap"
		});
	}
	else
	{
		layer.removeChildren();
		stage.remove(layer);
		stage.clear();
	}

	var curHeight = maxHeight;

	layer = new Kinetic.Layer();
	generateConnections(data.connections);
	generateSystems(data.wormholes);

	resizeMap();
	$(window).resize(function() {
		resizeMap();
	});

    // add the layer to the stage
    stage.add(layer);
}

function resizeMap()
{
	var stageWidth = maxWidth;
	var stageHeight = maxHeight;

	if (stageWidth < (totalWidth + 100))
		stageWidth = totalWidth + 100;

	if (stageHeight < totalHeight)
		stageHeight = totalHeight + 20;

	stage.setSize(stageWidth, stageHeight);
	$("#signatureMap").width(stageWidth);
	$("#signatureMapContainer").width(stageWidth);

	if ($(window).width() > stageWidth) {
		var left = Math.round(($(window).width() - stageWidth) / 2);
		$("#signatureMapContainer").css("left", left);
	} else
		$("#signatureMapContainer").css("left", 0);

	var top = $("#mapHeader").position().top + $("#mapHeader").height() + 14;
	$("#signatureMapContainer").css("top", top);

	$("#signatureMapContainer").height(maxHeight+20);
	$("#filler").height($("#signatureMapContainer").height()-15);
}

function generateConnections(data)
{
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

        connection.render(layer);
	}
}

function generateSystems(data)
{
	for (var i=0; i<data.length; i++)
	{
        var wormhole = new Wormhole(data[i].id);
        wormhole.setPosition(data[i].position.x, data[i].position.y);

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

        if (wormhole.isKspace()) {
            wormhole.addTitle(data[i].solarsystem.region);
            if (data[i].tradehub != null && data[i].tradehub.jumps != null)
                wormhole.addSubTitle(data[i].tradehub.jumps + " jumps to " + data[i].tradehub.name);
        }

        if (data[i].attributes != null)
        {
            if (data[i].attributes.persistant != null)
                wormhole.status.persistant = data[i].persistant;
            if (data[i].attributes.factionid != null)
                wormhole.solarsystem.faction = data[i].attributes.factionid;

            if (data[i].attributes.stations != null)            // Station system?
                wormhole.addIcon('images/eve/station.png');
            if (data[i].attributes.cyno != null)                // Caps in range?
                wormhole.addIcon('images/eve/cyno.png');
            if (data[i].attributes.hsisland != null)            // HS-Island?
                wormhole.addIcon('images/eve/stargate.red.png');
            if (data[i].attributes.direcths != null)            // Direct-HS
                wormhole.addIcon('images/eve/stargate.green.png');

            // Contested / faction warfare
            if (data[i].attributes.contested != null)
                wormhole.addIcon('images/eve/fw.contested.png');
            else if (data[i].attributes.fwsystem)
                wormhole.addIcon('images/eve/fw.png');
        }

        // Recent kills
        if (data[i].whsystem.class && data[i].kills != null) {
            if (data[i].kills.pvp > 0)
                wormhole.addIcon('images/eve/skull.red.png');
            if (data[i].kills.pve > 0)
                wormhole.addIcon('images/eve/skull.orange.png');
        }

        wormhole.render(layer);
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
	contextContainer += "<div id='wormholeContextMenu'><div style='padding: 10px;'><img src='images/loading.gif'> &nbsp; Loading context menu</div></div>";
	contextContainer += "<div id='wormholeContextFooter'>&nbsp;</div>";
	contextContainer += "</div>";
	$("#maincontent").append(contextContainer);

	$.ajax({
		url: "/index.php?module=scanning&section=map&action=contextmenu&ajax=1&id=" + whName,
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

	popupActive = true;
	loadingSigMap = true;
	blockMapRefresh = true;

	var className = "";
	var posLeft = x + whDefaultWidth + $("#signatureMap").position().left;
	var posTop = y + $("#signatureMap").position().top - 20;

	if (($(window).width()-posLeft) < 380)
	{
		posLeft = posLeft - whDefaultWidth - 400;
		className += "right";
	}
	else
		className += "left";

	var html = "<div class='sigInfo' id='whInfo"+system+"'>";
	html += "<div id='whInfo"+system+"Header' class='header"+className+"'></div>";
	html += "<div id='whInfo"+system+"Details' class='content"+className+"'>";
	html += "<img src='images/loading.gif'> &nbsp; Loading system data";
	html += "</div>";
	html += "<div id='whInfo"+system+"Footer' class='footer"+className+"'></div>";
	html += "</div>";

	$("#signatureMapContainer").append(html);
	$("#whInfo"+system).css("position","absolute");
	$("#whInfo"+system).css("left", posLeft);
	$("#whInfo"+system).css("top", posTop);
	$("#whInfo"+system).fadeIn();
	fetchWormholeDetails(system);
}
function closeWormholeDetails(system)
{
	$("#whInfo"+system).remove();
	popupActive = true;
	loadingSigMap = false;
	blockMapRefresh = false;
}
function fetchWormholeDetails(systemID)
{
	if (isContextOpen())
		return false;

	$.ajax({
		url: "/index.php?module=scanning&section=getwhdetails&ajax=1",
		data: {
			system: systemID
		},
		success: function(data) {
			if (isContextOpen())
				return false;

			$("#whInfo"+systemID+"Details").html(data);
            fetchSystemTradeHubs(systemID);
            fetchWormholeDetailsActivity(systemID);
		}
	});
}
function fetchSystemTradeHubs(systemID)
{
    if (isContextOpen())
        return false;

    $.ajax({
        url: "/index.php?module=scanning&section=getwhdetails&action=gettradehubs&ajax=1",
        data: {
            system: systemID
        },
        success: function(data) {
            if (isContextOpen())
                return false;

            $("#whinfotradehubs").html(data);
        }
    });
}
function fetchWormholeDetailsActivity(systemID)
{
	if (isContextOpen())
		return false;

	$.ajax({
		url: "/index.php?module=scanning&section=getwhdetails&action=getactivity&ajax=1",
		data: {
			system: systemID
		},
		success: function(data) {
			if (isContextOpen())
				return false;

			data = $.parseJSON(data);
			$("#whActivityDate").html(data.date);
			$("#whInfoActivity").html("<img src='"+data.url+"'/>");
		}
	});
}

function openConnectionDetails(who, x, y)
{
	popupActive = true;
	loadingSigMap = true;
	blockMapRefresh = true;

	var className = "";
	var posLeft = mousePosX+15;
	var posTop = mousePosY-50;

	if (($(window).width()-posLeft) < 380)
		className += "right";
	else
		className += "left";

	var popupID = "connInfo"+who.replace(",","-");
	var html = "<div class='sigInfo' id='"+popupID+"'>";
	html += "<div class='header"+className+"'></div>";
	html += "<div class='content"+className+"' id='conndetailsinfo' style='padding-left: 30px;'>";
	html += "<img src='images/loading.gif'> &nbsp; Loading wormhole data";
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

	$("#connInfo"+who).fadeIn();
	fetchConnectionInfo(who);
}
function closeConnectionDetails(who)
{
	var popupID = "connInfo"+who.replace(",","-");
	$("#"+popupID).remove();
	popupActive = true;
	loadingSigMap = false;
	blockMapRefresh = false;
}
function fetchConnectionInfo(who)
{
	$.ajax({
		url: "/index.php?module=scanning&section=getconndetails&ajax=1&connection="+who,
		success: function(data) {
			$("#conndetailsinfo").html(data);
			$("#jumplogsummary").html("<img src='images/loading.gif'> &nbsp; Loading jump log");
			// Jumplog halen
			$.ajax({
				url: "/index.php?module=scanning&section=getconndetails&action=jumplog&ajax=1&connection="+who,
				success: function(data) {
					$("#jumplogsummary").html(data);
				}
			});
		}
	});
}
function isContextOpen()
{
	if ($("#wormholeContext").length > 0)
		return true;
	else
		return false;
}
