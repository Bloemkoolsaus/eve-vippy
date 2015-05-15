function checkAPIs()
{
	$("div[data-apikey]").each(function() {

		var keyID = $(this).find("input[data-type=keyid]").val();
		var vCode = $(this).find("input[data-type=vcode]").val();

		$("[data-valid="+keyID+"]").removeClass("valid");
		$("[data-valid="+keyID+"]").removeClass("invalid");
		$("[data-valid="+keyID+"]").html("<img src='images/loading.gif'> &nbsp; Fetching API data...");
		
		$.ajax({
			url: "index.php?module=profile&section=api&ajax=1&action=validate&keyid="+keyID+"&vcode="+vCode,
			success: function(data) {
				data = $.parseJSON(data);
				$("[data-valid="+keyID+"]").html(data.status);
				if (data.valid) {
					$("[data-valid="+keyID+"]").addClass("valid");
				} else {
					$("[data-valid="+keyID+"]").addClass("invalid");
				}
			}
		});
	});
}

function addApiKey(keyid, vcode, valid, status)
{
	var data = {
		i: $("div[data-apikey]").length + 1,
		keyid: keyid,
		vcode: vcode,
		valid: valid,
		status: status,
	}

	$("#apikeys").append(Mustache.to_html($("#apiKeyTPL").html(), data));
	if (data.valid) {
		$("[data-valid="+data.keyid+"]").addClass("valid");
	} else {
		$("[data-valid="+data.keyid+"]").addClass("invalid");
	}
}