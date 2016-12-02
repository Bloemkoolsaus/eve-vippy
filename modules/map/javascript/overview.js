var loadingSigList = false;
var editingSigList = false;

var allowMapLoadingStart = true;
var allowMapLoadingFinish = true;

$(window).load(function() {
    reloadPage();
	if ($("#signatureMap").length > 0) {
		$(document).bind("contextmenu", function() { return false; });
        reloadSignatureMap(true);
	}

    // Scroll positie
    var scrollTop = getRequestParameter("scroll");
    if (scrollTop)
        $(document).scrollTop(scrollTop);
});

// Herlaad pagina. Garbage collector moet langskomen eens in de zoveel tijd!!
var pageTimer = 0;
function reloadPage()
{
    if (pageTimer >= 300000) {  // 5 minuten
        if (allowMapRefresh()) {
            document.location = "/map/"+$("#mapName").val()+"/"+$("#mapSystem").val()+"?scroll="+$(document).scrollTop();
            return true;
        }
    }
    setTimeout(reloadPage, 5000);
    pageTimer += 5000;
}

function reloadSignatureMap(noCache)
{
	if (!mapIsMassDeleteMode()) {
		loadSignatureMap();
		loadSignatureList(noCache);
	}
    setTimeout(reloadSignatureMap, 3000);
}

function disableMapRefresh()
{
    allowMapLoadingStart = false;
    allowMapLoadingFinish = false;
}
function enableMapRefresh()
{
    allowMapLoadingStart = true;
    allowMapLoadingFinish = true;
}
function allowMapRefresh()
{
    if (!allowMapLoadingStart)
        return false;
    if ($("#popup").length > 0)
        return false;

    return true;
}

function loadSignatureMap(action, params, force)
{
    if (!allowMapRefresh())
        return false;

    if (!action)
        action = "map";

    if (!params)
        params = { };
    params.ajax = 1;

    if (force) {
        params.nocache = 1;
        allowMapLoadingFinish = true;
    } else {
        if (!mapRendered())
            params.nocache = 1;
    }

    allowMapLoadingStart = false;
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/"+action+"/"+$("#mapSystem").val(),
        data: params,
        success: function(data) {
            var data = $.parseJSON(data);

            // Notifications
            $("#notificationContainter>.notification").remove();
            if (data.notifications != undefined && data.notifications.length > 0) {
                for (var n=0; n<data.notifications.length; n++) {
                    var notification = {
                        id: data.notifications[n].id,
                        type: data.notifications[n].type,
                        title: data.notifications[n].title,
                        content: data.notifications[n].content
                    };
                    $("#notificationContainter").append(Mustache.to_html($('#notificationTPL').html(), notification));
                }
            }

            if (force)
                allowMapLoadingFinish = true;

            // Map
            var mapData = data.map;
            if (mapData != "cached") {
                destroyPopup();
                generateMap(mapData);
            } else
                resizeMap();

            allowMapLoadingStart = true;
            var currentTime = new Date();
            $("#lastupdatetime").html(currentTime.toLocaleTimeString());
        }
    });
}

function editConnection(connectionID)
{
	$.ajax({
		url: "/map/connection/edit/"+connectionID,
        data: {
            ajax: 1
        },
		success: function(data) {
			showPopup("<div id='editConnectionPopup'>"+data+"</div>", 600, 400);
		}
	});
}

function addWormhole()
{
	$.ajax({
		url: "/map/"+$("#mapName").val()+"/add/"+$("#mapSystem").val(),
        data: { ajax: 1 },
		success: function(data) {
            showPopup(data, 450, 200);
            setTimeout(function() {
                setAutoComplete($("#fromname"));
                setAutoComplete($("#toname"));
            }, 1500);
		}
	});
}

function deleteWormhole(systemName, removeConnected)
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/remove/"+systemName+"/"+((removeConnected)?"connected":""),
        data: { ajax: 1 },
        complete: function() {
            loadSignatureMap(false, false, true);
        }
    });
}

function setSystemPermanent(systemName)
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/permanent/"+systemName,
        data: { ajax: 1 },
        complete: function() {
            loadSignatureMap(false, false, true);
        }
    });
}

function unsetSystemPermanent(systemName)
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/permanent/"+systemName,
        data: { ajax: 1 },
        complete: function() {
            loadSignatureMap(false, false, true);
        }
    });
}

function massDeleteWormholes()
{
	$("#mapButtons").hide();
	$("#massDeleteInstruction").fadeIn();
	mapSetMassDelete();
}
function cancelMassDeleteWormholes()
{
	$("#massDeleteInstruction").fadeOut(100,function() {$("#mapButtons").fadeIn();});
	mapUnSetMassDelete();
}

function clearChain()
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/clear",
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 450, 200);
        }
    });
}
function confirmClearChain()
{
	$("#clearChainConfirmation").fadeOut(100,function() {$("#mapButtons").fadeIn();});
	loadSignatureMap("&clearchain=1&nocache=1");
}
function cancelClearChain()
{
	$("#clearChainConfirmation").fadeOut(100,function() {$("#mapButtons").fadeIn();});
}



function copypasteAnomalies()
{
	$.ajax({
		url: "/map/anomalies/copypaste/"+$("#mapName").val()+"/"+$("#mapSystem").val(),
        data: { ajax: 1 },
		success: function(data) {
			showPopup(data, 600, 280);
		}
	});
}

function hideSigInfo(sigID)
{
	$("#sigInfo"+sigID).fadeOut();
	loadingSigList = false;
}

function removeAnomaly(anomID)
{
	document.location = "index.php?module=scanning&section=anoms&action=remove&id="+anomID;
}

function showActivePilots()
{
	showLoadingPopup();
	$.ajax({
		url: "/index.php?module=scanning&section=activepilots&ajax=1",
		success: function(data) {
			setPopupContent(data,500,400);
		}
	});
}


function addToKnownSystems(systemName)
{
    $.ajax({
        url: "/map/knownwormhole/add/"+systemName,
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 200);
        }
    });
}
function removeFromKnownSystems(systemName)
{
	$.ajax({
        url: "/map/knownwormhole/remove/"+systemName,
        data: { ajax: 1 },
		success: function(data) {
			showPopup(data, 500, 200);
		}
	});
}

function addFleet()
{
    $.ajax({
        url: "/fleets/fleet/add",
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 450, 200);
        }
    });
}