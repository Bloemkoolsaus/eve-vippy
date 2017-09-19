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
    //setTimeout(reloadPage, 5000);
    pageTimer += 5000;
}

function reloadSignatureMap(noCache)
{
	if (!mapIsMassDeleteMode()) {
		loadSignatureMap();
		loadSignatureList(noCache);
	}
    setTimeout(reloadSignatureMap, 1500);
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
    if (!allowMapRefresh() && !force)
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
            data = $.parseJSON(data);

            // Notifications
            $("#notificationContainter>.notification").remove();
            if (data.notifications !== undefined && data.notifications.length > 0) {
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
            $("#notificationContainter>.notification").each(function() {
                var notificationID = $(this).attr("data-notification");
                if (isNaN(notificationID)) {
                    $("div.notification[data-notification="+notificationID+"]>img").remove();
                }
            });

            if (force)
                allowMapLoadingFinish = true;

            // Map
            var mapData = data.map;
            if (mapData != "cached") {
                destroyPopup();
                generateMap(mapData);
            }

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

function renameWormhole(wormholeID)
{
    if (wormholeID) {
        // Open de popup
        $.ajax({
            url: "/map/"+$("#mapName").val()+"/rename/"+wormholeID,
            data: { ajax: 1 },
            success: function(data) {
                showPopup(data, 450, 200);
            }
        });
    } else {
        // Nieuwe naam opslaan
        $("button#rename-wormhole-submit>img").attr("src","/images/loading.gif");
        $.ajax({
            type: "POST",
            url: "/map/"+$("#mapName").val()+"/rename/"+$("input[name=rename-wormhole-id]").val(),
            data: {
                name: $("input[name=rename-wormhole-name]").val(),
                ajax: 1
            },
            complete: function() {
                destroyPopup();
            }
        });
    }
}

function deleteWormhole(systemName, removeConnected)
{
    if (systemName) {
        // Open de popup
        $.ajax({
            url: "/map/"+$("#mapName").val()+"/remove/"+systemName+"/"+((removeConnected)?"connected":""),
            data: { ajax: 1 },
            success: function(data) {
                showPopup(data, 450, 200);
            }
        });
    } else {
        // Verwijder systeem
        $("button#remove-wormhole-submit>img").attr("src","/images/loading.gif");
        $.ajax({
            type: "POST",
            url: "/map/"+$("#mapName").val()+"/remove/"+$("input[name=remove-wormhole-id]").val(),
            data: {
                connected: $("input[name=remove-wormhole-connected]").val(),
                confirmed: $("input[name=remove-wormhole-confirmed]").val(),
                ajax: 1
            },
            complete: function() {
                loadSignatureMap(false, false, true);
                destroyPopup();
            }
        });
    }
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
		url: "/map/"+$("#mapName").val()+"/anomalies/copypaste/"+$("#mapSystem").val(),
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


function editKnownSystems(systemName)
{
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/knownwormhole/edit/"+systemName,
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 250);
        }
    });
}
function storeKnownSystem()
{
    $.ajax({
        type: "POST",
        url: "/map/"+$("#mapName").val()+"/knownwormhole/save/"+$("input[name=known-system-name]").val(),
        data: {
            id: $("input[name=known-system-id]").val(),
            name: $("input[name=known-system-title]").val(),
            status: $("select[name=known-system-status]").val(),
            ajax: 1
        },
        complete: function() {
            destroyPopup();
            loadSignatureMap(false, false, true);
        }
    });
}
function removeKnownSystem()
{
    $.ajax({
        type: "POST",
        url: "/map/"+$("#mapName").val()+"/knownwormhole/remove/"+$("input[name=known-system-name]").val(),
        data: {
            confirmed: 1,
            ajax: 1
        },
        complete: function() {
            destroyPopup();
            loadSignatureMap(false, false, true);
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

function addBroadcast(wormholeID)
{
    $.ajax({
        url: "/map/rally/add/"+wormholeID,
        data: { ajax: 1 },
        complete: function() {
            loadSignatureMap(false, false, true);
        }
    });
}
function removeBroadcast(wormholeID)
{
    $.ajax({
        url: "/map/rally/remove/"+wormholeID,
        data: { ajax: 1 },
        complete: function() {
            loadSignatureMap(false, false, true);
        }
    });
}