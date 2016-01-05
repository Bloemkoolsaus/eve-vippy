
function loadSignatureList(extraURL, addNoCache)
{
	if (editingSigList)
		return false;

	if ((!loadingSigList && $("#disabledPage").length == 0 && $("#signatureList").is(":visible")) || extraURL)
	{
		var system = $("#sigsystem").val();
		if (system.length == 0)
			return false;

		if (!extraURL)
			extraURL = "";

		if ($("#signatureList").html() == "" || addNoCache)
			extraURL += "&nocache=1";

		if ($("#sigsSortBy").length > 0)
			extraURL += "&sortby=" + $("#sigsSortBy").val();

		if ($("#sigsSortDir").length > 0)
			extraURL += "&sortdir=" + $("#sigsSortDir").val();

		loadingSigList = true;
		$.ajax({
			url: "index.php?module=scanning&section=map&action=siglist&ajax=1&system="+system+extraURL,
			success: function(data) {
				if (data != "cached" && !editingSigList) {
                    $("#signatureList").html(data);
                    setAutoCompletes()
                }

				loadingSigList = false;
			}
		});
	}
}

function showSignatureList()
{
	$("#signatureToggle").hide();
	$("#signatureForm").fadeIn();
	$("#signatureList").html("<div><br /><img src='images/loading.gif'> Loading signatures</div>");
	$("#signatureList").fadeIn();
	loadSignatureList("&nocache=1");
}

function sortSigList(sortByNew)
{
	var sortBy = $("#sigsSortBy").val();
	var sortDir = $("#sigsSortDir").val();
	if (sortBy == sortByNew)
		$("#sigsSortDir").attr("value", (sortDir=="DESC")?"ASC":"DESC");
	else
		$("#sigsSortBy").attr("value", sortByNew);

	loadSignatureList("&nocache=1");
}

function editSig(id)
{
	editingSigList = true;

	$("#siglist"+id+"id").hide();
	$("#siglist"+id+"type").hide();
	$("#siglist"+id+"info").hide();
	$("#siglist"+id+"signalstrength").hide();
	$("#siglist"+id+"buttons").hide();

	$("#sigedit"+id+"id").fadeIn();
	$("#sigedit"+id+"type").fadeIn();
	$("#sigedit"+id+"info").fadeIn();
	$("#sigedit"+id+"signalstrength").fadeIn();
	$("#sigedit"+id+"buttons").fadeIn();
}

function saveEditSig(id)
{
	var reqURL = "index.php?module=scanning&section=map&action=updatesignature&id="+id+"&ajax=1";
	reqURL += "&sig=" + $("#siginput"+id+"id").val();
	reqURL += "&type=" + $("#siginput"+id+"type").val();
	if ($("#siginput"+id+"type").val() == "wh")
		reqURL += "&typeid=" + $("#siginput"+id+"whtype").val();
	reqURL += "&info=" + $("#siginput"+id+"info").val();
	if ($("#siginput"+id+"signalstrength").length > 0)
		reqURL += "&signalstrength=" + $("#siginput"+id+"signalstrength").val();

	$("#sigedit"+id+"id").fadeOut(500, function() { $("#siglist"+id+"id").show(); } );
	$("#sigedit"+id+"type").fadeOut(500, function() { $("#siglist"+id+"type").show(); } );
	$("#sigedit"+id+"whtype").fadeOut(500, function() { $("#siglist"+id+"whtype").show(); } );
	$("#sigedit"+id+"info").fadeOut(500, function() { $("#siglist"+id+"info").show(); } );
	$("#sigedit"+id+"signalstrength").fadeOut(500, function() { $("#siglist"+id+"whtype").show(); } );
	$("#sigedit"+id+"buttons").fadeOut(500, function() { $("#siglist"+id+"buttons").show(); } );
	$("#siglist"+id+"info").html("<img src='images/loading.gif'> Saving");

	editingSigList = false;
	$.ajax({
		url: reqURL,
		success: function(data) {
			loadingSigList = false;
			loadSignatureList(false,true);
		}
	});
}

function markFullyScanned(systemID)
{
	loadSignatureList("&fullyscanned="+systemID);
}


function showSigInfo(sigID)
{
    var top = $("#signatureList"+sigID).position().top-30;
    var left = $("#signatureList"+sigID).position().left+$("#signatureList"+sigID).width();

    if ($(window).width() < 930)
    {
        left -= ($("#sigInfo"+sigID).width()+60);
        $("#sigInfo"+sigID).find("div.content").removeClass("contentleft");
        $("#sigInfo"+sigID).find("div.content").addClass("contentright");
    }
    else
    {
        $("#sigInfo"+sigID).find("div.content").removeClass("contentright");
        $("#sigInfo"+sigID).find("div.content").addClass("contentleft");
    }

	$("#sigInfo"+sigID).css("left", left);
	$("#sigInfo"+sigID).css("top", top);
    $("#sigInfo"+sigID).fadeIn();
	loadingSigList = true;
}