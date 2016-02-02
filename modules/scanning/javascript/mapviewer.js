var loadingSigMap = false;
var loadingSigList = false;
var editingSigList = false;

$(window).load(function() {
	if ($("#signatureMap").length > 0) {
		reloadSignatureMap();
		setTimeout(refreshSigMapPage, 600000);
		$(document).bind("contextmenu", function() { return false; });
	}
	if (isIGB()) {
		callLocationTracker();
	}
});

function callLocationTracker()
{
	$.ajax({
		url: "index.php?module=scanning&section=map&action=locationtracker&ajax=1"
	});
	setTimeout(callLocationTracker, 1500);
}

function refreshSigMapPage()
{
	document.formChangeCurrentSystem.submit();
}
function refreshToCurrentSystem()
{
	showLoadingPopup();
	$("#currentSystem").val("current");
	document.formChangeCurrentSystem.submit();
}

function reloadSignatureMap(noReload)
{
	if (!mapIsMassDeleteMode())
	{
		loadSignatureMap();
		loadSignatureList();
		setTimeout(reloadSignatureMap, 3000);
	}
}

function mayResetMap()
{
	if ($("#createNoticePopup").length > 0)
		return false;

	if ($("#editConnectionPopup").length > 0)
		return false;

	return true;
}

function loadSignatureMap(extraURL, ignorepopup)
{
	if (loadingSigMap)
		return false;

	if (($("#disabledPage").length == 0 || ignorepopup) || extraURL)
	{
		var system = $("#sigsystem").val();
		if (system.length == 0)
			return false;

		if (!extraURL)
			extraURL = "";

		if (!mapRendered())
			extraURL = "&nocache=1";

		loadingSigMap = true;
		$.ajax({
			url: "index.php?module=scanning&section=map&action=sigmap&ajax=1&system="+system+extraURL,
			success: function(data) {
				if (!mayResetMap())
					return false;

				var currentTime = new Date();
				var hour = currentTime.getHours()-0;
				var min = currentTime.getMinutes()-0;
				var sec = currentTime.getSeconds()-0;

				if (hour < 10)
					hour = "0"+hour;
				if (min < 10)
					min = "0"+min;
				if (sec < 10)
					sec = "0"+sec;

				$("#lastupdatetime").html(hour+":"+min+":"+sec);

				if (data != "cached")
				{
					var data = $.parseJSON(data);
					destroyPopup();
					generateMap(data);

					// Zaten er notices in?
					if (data.notices.length > 0)
					{
						var n = 0;
						var noticeIDs = new Array();
						for (n=0; n<data.notices.length; n++) {
							addNotice(data.notices[n]);
							noticeIDs.push(data.notices[n].id);
						}
						$("[rel=notice]").each(function() {
							var id = $(this).attr("id").replace("shownotice","");
							if ($.inArray(id, noticeIDs) < 0)
								$("#shownotice"+id).fadeOut("slow", function() { $("#shownotice"+id).remove(); });
						});
					}
				}
				loadingSigMap = false;
			}
		});
	}
}

function editConnection(from,to)
{
	$.ajax({
		url: "index.php?module=scanning&section=map&action=editconnection&ajax=1&from="+from+"&to="+to,
		success: function(data) {
			showPopup("<div id='editConnectionPopup'>"+data+"</div>", 600, 400);
		}
	});
}

function addWormhole(from,to)
{
	$.ajax({
		url: "index.php?module=scanning&section=map&action=addwormhole&ajax=1&from="+from+"&to="+to,
		success: function(data) {
			if (data == "added")
			{
				cancelAddWormhole();
				showLoadingPopup();
				loadSignatureMap("&nocache=1",true);
			}
			else
			{
				$("#mapButtons").hide();
				$("#addWormholeForm").fadeIn();
				$("#addWormholeForm").html(data);
			}
		}
	});
}

function cancelAddWormhole()
{
	$("#addWormholeForm").fadeOut(250,function() {
		$("#mapButtons").show();
		$("#addWormholeForm").html("");
	});
}

function switchSystem(system)
{
	showLoadingPopup();
	$("#currentSystem").attr("value",system);
	if ($("#wormholeContext").length > 0)
		$("#wormholeContext").remove();
	document.formChangeCurrentSystem.submit();
}

function selectSignatureType(sigID)
{
	var sigType =  $("#sigtype").val();

	if (sigType == "wh")
    {
        var data = { sigtype: sigType, sigID: sigID };
        var html = "";
        if ($("td[rel=addsig_wormhole]").attr("data-whtype-input") == "select")
            html = Mustache.to_html($("#whTypeSelectTPL").html(), data);
        else
            html = Mustache.to_html($("#whTypeInputTPL").html(), data);

        $("#whTypeInputContainer").html(html);

		$("td[rel=addsig_wormhole]").show();
		$("#whtype").focus();
	}
    else
    {
		$("td[rel=addsig_wormhole]").hide();
		$("#siginfo").focus();
	}
}

function selectSignatureWhType(select)
{
    if (select.val() == "other")
    {
        var data = { whType: select.attr("data-whtype"), sigID: select.attr("data-sigid") };
        var html = Mustache.to_html($("#whTypeInputTPL").html(), data);
        if (select.attr("data-sigid")) {
            $("#signWhTypeInput"+select.attr("data-sigid")).html(html);
        } else {
            $("#whTypeInputContainer").html(html);
        }
    }
}

function addSignature()
{
	var reqURL = "index.php?module=scanning&section=map&action=addsignature&ajax=1";
	reqURL += "&sig=" + $("#sigid").val();
	reqURL += "&type=" + $("#sigtype").val();

	if ($("#sigtype").val() == "wh")
		reqURL += "&typeid=" + $("#whTypeInputContainer>[name=whtype]").val();

	reqURL += "&info=" + $("#siginfo").val();

	$("#sigid").attr("value","");
	$("#sigtype").attr("value","");
	$("#siginfo").attr("value","");
	$("#whtype").attr("value","");
	$("#sigid").focus();

	$.ajax({
		url: reqURL,
		success: function(data) {
			loadSignatureList();
		}
	});
}

function removeSig(id)
{
	var reqURL = "index.php?module=scanning&section=map&action=deletesignature&id="+id+"&ajax=1";
	$("#signatureList"+id).fadeOut();
	$.ajax({
		url: reqURL,
		success: function(data) {
			// Doe niets. Lijst-update gaat vanzelf!
		}
	});
}

function saveWormhole()
{
	var url = "&rename="+$("#renameid").val();
	url += "&name="+$("#renamename").val();
	url += "&status="+$("#whstatus").val();
	url += "&notes="+document.getElementById("notes").value;
	url += "&nocache=1";
	loadSignatureMap(url);
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

function snapToGrid()
{
	$("#mapButtons").hide();
	$("#snapToGridConfirmation").fadeIn();
}
function confirmSnapToGrid()
{
	$("#snapToGridConfirmation").fadeOut(100,function() {$("#mapButtons").fadeIn();});
	showLoadingPopup();
	$.ajax({
		url: "index.php?module=scanning&section=snaptogrid&ajax=1",
		success: function(data) {
			loadSignatureMap("",true);
		}
	});
}
function cancelSnapToGrid()
{
	$("#clearChainConfirmation").fadeOut(100,function() {$("#mapButtons").fadeIn();});
}


function copypasteAnoms()
{
	$.ajax({
		url: "index.php?module=scanning&section=anoms&action=copypaste&ajax=1",
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
		url: "index.php?module=scanning&section=activepilots&ajax=1",
		success: function(data) {
			setPopupContent(data,500,400);
		}
	});
}

function showTrackingOnlyModeHelp()
{
	$.ajax({
		url: "index.php?module=scanning&section=trackingonly&action=showhelp&ajax=1",
		success: function(data) {
			showPopup(data,500,400);
		}
	});
}

function enableTrackingOnly()
{
	document.location = "index.php?module=scanning&section=trackingonly&action=enabletrackingonly";
}

function disableTrackingOnly()
{
	document.location = "index.php?module=scanning&section=trackingonly&action=disabletrackingonly";
}

function addToKnownSystems(system)
{
	$.ajax({
		url: "index.php?module=scanning&section=map&action=addtoknownsystems&ajax=1&system="+system,
		success: function(data) {
			showPopup(data, 400, 250);
		}
	});
}
function removeFromKnownSystems(system)
{
	$.ajax({
		url: "index.php?module=scanning&section=map&action=addtoknownsystems&ajax=remove&system="+system,
		success: function(data) {
			showPopup(data,500,200);
		}
	});
}

function mapLegend()
{
	$.ajax({
		url: "index.php?module=scanning&section=maplegend&ajax=1",
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
		url: "index.php?module=scanning&section=exitfinder&ajax=1",
		data: {
			system: $("#exitFinderSystem").val(),
		},
		success: function(data) {
			$("#exitFinderResults").html(data);
		}
	});
}