function ajaxSendGet(script, handlerfunction, args) {

	if (!args)
		var args = new Array();

	var xmlHttp;
	try {
		// Firefox, Opera 8.0+, Safari
		xmlHttp = new XMLHttpRequest();
	} catch (e) {
		// Internet Explorer
		try {
			xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				// Browser does not support ajax
				alert("Your browser does not support AJAX!");
				return false;
			}
		}
	}

	xmlHttp.onreadystatechange = function() {
		if (xmlHttp.readyState == 4) {
			args["response"] = xmlHttp.responseText;
			if (handlerfunction)
				handlerfunction.call(this, args, false);
		}
	}

	xmlHttp.open("GET", script, true);
	xmlHttp.send(null);
}

function fillAjaxDiv(params) {

	if ((!params["div"]) || (!params["response"]))
		return false;

	document.getElementById(params["div"]).innerHTML = params["response"];
	return true;
}
