var loadingSigList = false;
var editingSigList = false;

var allowMapLoadingStart = true;
var allowMapLoadingFinish = true;

$(window).load(function() {
	if ($("#signatureMap").length > 0) {
		reloadSignatureMap(true);
		$(document).bind("contextmenu", function() { return false; });
	}
});

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

function loadSignatureMap(action, params)
{
    if (!allowMapRefresh())
        return false;

    if (!action)
        action = "map";

    if (!params)
        params = { };
    if (!mapRendered())
        params.nocache = 1;
    params.ajax = 1;

    allowMapLoadingStart = false;
    $.ajax({
        url: "/map/"+$("#mapName").val()+"/"+action+"/"+$("#mapSystem").val(),
        data: params,
        success: function(data) {
            if (data != "cached") {
                destroyPopup();
                var data = $.parseJSON(data);
                generateMap(data);
            }
            allowMapLoadingStart = true;
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

function addWormhole(from,to)
{
	$.ajax({
		url: "/index.php?module=scanning&section=map&action=addwormhole&ajax=1",
        data: {
            from: from,
            to: to
        },
		success: function(data) {
			if (data == "added") {
				cancelAddWormhole();
				showLoadingPopup(function() {
                    location.reload();
                });
			} else {
				$("#mapButtons").hide();
				$("#addWormholeForm").fadeIn();
				$("#addWormholeForm").html(data);
                setAutoCompletes();
			}
		}
	});
}

function deleteWormhole(wormholeID, removeConnected)
{
	var url = "&delete="+wormholeID+"&nocache=1";
	if (removeConnected)
		url += "&removeConnected=1";

	loadSignatureMap(url);
	if ($("#wormholeContext").length > 0)
		$("#wormholeContext").remove();
}

function setSystemPermanent(wormholeID)
{
	loadSignatureMap("&setpermanent="+wormholeID);
	if ($("#wormholeContext").length > 0)
		$("#wormholeContext").remove();
}

function unsetSystemPermanent(wormholeID)
{
	loadSignatureMap("&unsetpermanent="+wormholeID);
	if ($("#wormholeContext").length > 0)
		$("#wormholeContext").remove();
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
	$("#mapButtons").hide();
	$("#clearChainConfirmation").fadeIn();
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



function copypasteAnoms()
{
	$.ajax({
		url: "/index.php?module=scanning&section=anoms&action=copypaste&ajax=1",
		success: function(data) {
			showPopup(data, 500, 400);
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
function removeAnomalies()
{
	document.location = "index.php?module=scanning&section=anoms&action=remove&id=all";
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
        url: "/map/knownwormhole/add/"+systemName+"/",
        data: { ajax: 1 },
        success: function(data) {
            showPopup(data, 500, 200);
        }
    });
}
function removeFromKnownSystems(systemName)
{
	$.ajax({
        url: "/map/knownwormhole/remove/"+systemName+"/",
        data: { ajax: 1 },
		success: function(data) {
			showPopup(data, 500, 200);
		}
	});
}

function mapLegend()
{
	$.ajax({
		url: "/index.php?module=scanning&section=maplegend&ajax=1",
		success: function(data) {
			showPopup(data,930,750);
		}
	});
}

function showExitFinder(system)
{
	$('#exitFinderForm').fadeIn();
	if (system) {
		$("#exitFinderSystem").val(system);
	}
}

function exitFinder()
{
	$("#exitFinderResults").html("<img src='/images/loading.gif'> Calculating..");

	$.ajax({
		url: "/index.php?module=scanning&section=exitfinder&ajax=1",
		data: {
			system: $("#exitFinderSystem").val(),
		},
		success: function(data) {
			$("#exitFinderResults").html(data);
		}
	});
}