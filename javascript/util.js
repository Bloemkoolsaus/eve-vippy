function addDiv(newDivId, element) 
{
	if (!element)
		var element = document.body;
	if (!document.getElementById(newDivId)) {
		var newDiv = document.createElement("div");
		newDiv.id = newDivId;
		element.appendChild(newDiv);
	}
}

function removeDiv(id, element) 
{
	if (!element)
		var element = document.body;
	if (document.getElementById(id))
		element.removeChild(document.getElementById(id));
}

function showHelp(help)
{
	var content = "<div class='help' style='padding: 15px; text-align: left;'>";
	content += "<img src='images/default/information.png' align='left' style='margin-right: 15px; margin-bottom: 15px;' />";
	content += help + "</div>";
	
	content += "<div class='help' style='padding: 15px; text-align: center;'>";
	content += "<button type='button' onclick='destroyPopup();'> OK </button>";
	content += "</div>";
	
	showPopup(content, 400, 200);
	return false;
}

$.fn.hasAttr = function(attrName) {
	return this.attr(attrName) !== undefined;
};

function showLoadingPopup()
{
	showPopup("Loading", 300, 100);
}

function showLoadingDiv(divid)
{
	$("#"+divid).html("<p>Loading...</p>");
}