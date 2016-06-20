
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
			url: "/index.php?module=scanning&section=map&action=siglist&ajax=1&system="+system+extraURL,
			success: function(data) {
				if (data != "cached" && !editingSigList) {
                    $("#signatureList").html(data);
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
	$("#signatureList").html("<div><br /><img src='/images/loading.gif'> Loading signatures</div>");
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

    if ($("#sigedit"+id+"id").is(":visible"))
        return false;

	$("div[id^=siglist]").show();
	$("div[id^=sigedit]").hide();

	$("div[id^=siglist"+id+"]").hide();
	$("div[id^=sigedit"+id+"]").fadeIn();

    var data = {
        sigID: id,
        whType: $("#signWhTypeInput"+id).attr("data-sig-whtype")
    };
    var html = "";
    if ($("#signWhTypeInput"+id).attr("data-whtype-input") == "select")
        html = Mustache.to_html($("#whTypeSelectTPL").html(), data);
    else
        html = Mustache.to_html($("#whTypeInputTPL").html(), data);

    $("#signWhTypeInput"+id).html(html);
}

function setWhTypeAutocomplete(sigID)
{
    //setAutoComplete($("#siginput"+sigID+"whtype"));
}

function saveEditSig()
{
    var sigID = 0;
    $("input[name=siginfo]").each(function() {
        if ($(this).is(":visible"))
            sigID = $(this).attr("data-sigid");
    });
    var params = {
        id: sigID,
        sig: $("input[name=sigid][data-sigid="+sigID+"]").val(),
        type: $("select[name=sigtype][data-sigid="+sigID+"]").val(),
        typeid: $("#siginput"+sigID+"whtype").val(),
        info: $("input[name=siginfo][data-sigid="+sigID+"]").val(),
        signalstrength: $("input[name=sigstrength][data-sigid="+sigID+"]").val()
    };

	$("#sigedit"+sigID+"id").fadeOut(500, function() { $("#siglist"+sigID+"id").show(); } );
	$("#sigedit"+sigID+"type").fadeOut(500, function() { $("#siglist"+sigID+"type").show(); } );
	$("#sigedit"+sigID+"whtype").fadeOut(500, function() { $("#siglist"+sigID+"whtype").show(); } );
	$("#sigedit"+sigID+"info").fadeOut(500, function() { $("#siglist"+sigID+"info").show(); } );
	$("#sigedit"+sigID+"signalstrength").fadeOut(500, function() { $("#siglist"+sigID+"whtype").show(); } );
	$("#sigedit"+sigID+"buttons").fadeOut(500, function() { $("#siglist"+sigID+"buttons").show(); } );
	$("#siglist"+sigID+"info").html("<img src='/images/loading.gif'> Saving");

	editingSigList = false;
	$.ajax({
		url: "/index.php?module=scanning&section=map&action=updatesignature&ajax=1",
        data: params,
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