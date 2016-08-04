
function loadSignatureList(noCache)
{
	if (editingSigList)
		return false;

	if (!loadingSigList && $("#disabledPage").length == 0 && $("#signatureList").is(":visible"))
	{
        var params = { ajax: 1 };
		if (noCache)
			params.nocache = 1;

		loadingSigList = true;
		$.ajax({
			url: "/map/"+$("#mapName").val()+"/signatures/"+$("#mapSystem").val(),
            data: params,
			success: function(data) {
				if (data != "cached" && !editingSigList) {
                    // 'Oude' sigs
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
                    var tpl = $("#signatureTPL").html();
                    for (var s=0; s<signatures.length; s++) {
                        $("#signatureTable").append(Mustache.to_html(tpl, signatures[s]));
                    }
                }

				loadingSigList = false;
			}
		});
	}
}

function markFullyScanned(systemID)
{
	// loadSignatureList();
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