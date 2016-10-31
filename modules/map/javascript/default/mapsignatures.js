
function loadSignatureList(noCache)
{
	if (editingSigList)
		return false;

	if (!loadingSigList && $("#disabledPage").length == 0 && $("#signatureList").is(":visible"))
	{
		loadingSigList = true;
		$.ajax({
            url: "/map/signatures/"+((noCache)?"nocache":""),
            data: {
                map: $("#mapName").val(),
                system: $("#mapSystem").val(),
                ajax: 1
            },
			success: function(data) {
				if (data != "cached" && !editingSigList) {
                    // Oude sigs
                    var old = [];
                    $("tr[rel=signature]").each(function() {
                        old[$(this).attr("data-id")] = {
                            id: $(this).attr("data-id"),
                            type: $(this).attr("data-type")
                        };
                        $(this).remove();
                    });

                    // Nieuwe sigs
                    var signatures = $.parseJSON(data);
                    $("#signaturesCount").html(signatures.length);
                    for (var s=0; s<signatures.length; s++) {
                        $("#signatureTable").append(Mustache.to_html($("#signatureTPL").html(), {
                            id: signatures[s].id,
                            sigid: signatures[s].sigid,
                            type: signatures[s].type,
                            whtype: (signatures[s].wormhole!=undefined) ? signatures[s].wormhole.type : "",
                            info: signatures[s].info,
                            scanage: signatures[s].scanage,
                            scanuser: signatures[s].scanuser,
                            updateage: signatures[s].updateage,
                            updateuser: signatures[s].updateuser
                        }));
                    }
                }

				loadingSigList = false;
			}
		});
	}
}

function signatureTooltip(id)
{
    var html = Mustache.to_html($("#signatureTooltipTPL").html(), {
        scanage: $("tr[data-id="+id+"]").attr("data-scannedon"),
        scanuser: $("tr[data-id="+id+"]").attr("data-scannedby"),
        updateage: $("tr[data-id="+id+"]").attr("data-updateon"),
        updateuser: $("tr[data-id="+id+"]").attr("data-updateby")
    });

    var tip = new Tooltip("sig"+id);
    tip.render(html);
    tip.show();
}

function signatureCloseTooltip(id)
{
    closeTooltip("sig"+id);
}

function deleteSignature(id)
{
    $("tr[rel=signature][data-id="+id+"]").fadeOut("fast", function() {
        $.ajax({
            url: "/map/signatures/delete/"+id,
            data: {
                map: $("#mapName").val(),
                system: $("#mapSystem").val(),
                ajax: 1
            },
            complete: function() {
                loadSignatureList(true);
            }
        });
    });
}

function clearSignatures()
{
    $.ajax({
        url: "/map/signatures/delete/all",
        data: {
            map: $("#mapName").val(),
            system: $("#mapSystem").val(),
            ajax: 1
        },
        complete: function() {
            loadSignatureList(true);
        }
    });
}

function addSignature()
{
    $.ajax({
        type: "POST",
        url: "/map/signatures/store",
        data:  {
            map: $("#mapName").val(),
            system: $("#mapSystem").val(),
            id: 0,
            sigid: $("#sigId").val(),
            type: $("#sigType").val(),
            whtype: $("#whType").val(),
            info: $("#sigName").val(),
            ajax: 1
        },
        complete: function() {
            $("#sigId").val("");
            $("#sigType").val("");
            $("#whType").val("");
            $("#sigName").val("");
            $("#sigId").focus();
            loadSignatureList(true);
        }
    });
}

function editSignature(id)
{
    $("tr.sigedit").each(function() {
        editSignatureCancel($(this).attr("data-id"));
    });

    editingSigList = true;
    var row = $("tr[data-id="+id+"]");
    var data = {
        id: id,
        sigid: row.find("td.sigID").html(),
        type: row.attr("data-type"),
        whtype: row.attr("data-whtype"),
        info: row.find("td.sigInfo").html(),
        scanage: row.find("td.sigUpdate").html()
    };

    var html = Mustache.to_html($("#signatureEditTPL").html(), data);
    $("tr[data-id="+id+"]").replaceWith(html);

    $("#sigType"+id).val(data.type);
    $("input.signame").focus();
    $("input.signame").select();

    if (data.type == "wh") {
        $("input.signame").width($("td.sigInfo").width()-$("input.whtype").width()-50);
    } else {
        $("input.whtype").hide();
        $("input.signame").width($("td.sigInfo").width()-50);
    }
}

function editSignatureCancel(id)
{
    editingSigList = false;
    var html = Mustache.to_html($("#signatureTPL").html(), {
        id: id,
        sigid: $("#sigId"+id).val(),
        type: $("#sigType"+id).val(),
        whtype: $("#whType"+id).val(),
        info: $("#sigName"+id).val(),
        scanage: $("tr[data-id="+id+"]").find("sigUpdate").html()
    });
    $("tr[data-id="+id+"]").replaceWith(html);
}

function storeSignature()
{
    var id = $("tr.sigedit").attr("data-id");
    $.ajax({
        url: "/map/signatures/store",
        data: {
            map: $("#mapName").val(),
            system: $("#mapSystem").val(),
            id: id,
            sigid: $("#sigId"+id).val(),
            type: $("#sigType"+id).val(),
            whtype: $("#whType"+id).val(),
            info: $("#sigName"+id).val(),
            ajax: 1
        },
        complete: function() {
            editingSigList = false;
            loadSignatureList(true);
        }
    });
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


function selectSignatureType(sigID)
{
    var sigType =  $("#sigType").val();

    if (sigType == "wh")
    {
        var html = "";
        var data = {
            sigtype: sigType,
            sigID: sigID
        };
        if ($("td[rel=addsig_wormhole]").attr("data-whtype-input") == "select")
            html = Mustache.to_html($("#whTypeSelectTPL").html(), data);
        else
            html = Mustache.to_html($("#whTypeInputTPL").html(), data);

        $("td[rel=addsig_wormhole]").show();
        $("#whTypeInputContainer").html(html);
        $("#whType").focus();
    }
    else
    {
        $("td[rel=addsig_wormhole]").hide();
        $("#sigName").focus();
    }
}

function signaturesCopyPaste()
{
    $.ajax({
        url: "/map/signatures/copypaste?ajax=1",
        type: "POST",
        data: {
            map: $("#mapName").val(),
            system: $("#mapSystem").val(),
            signatures: $("textarea[name=copypastesignatures]").val()
        },
        complete: function() {
            $("textarea[name=copypastesignatures]").val("");
            loadSignatureList(true);
        }
    })
}