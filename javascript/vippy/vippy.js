$(document).ready(function() {
	setMaincontainerPosition();
});
$(window).resize(function() {
	setMaincontainerPosition();
});

function setMaincontainerPosition()
{
	// Hoogte
	$("#maincontainer").removeClass("margin");	
	if ($("#mapHeader").length == 0)
	{	
		if ($("#maincontainer").outerHeight()+$("#header").outerHeight() < $(window).height()-50) {
			$("#maincontainer").addClass("margin");
		}
	}
	
	// Breedte
	$("#maincontainer").removeClass("width");	
	if ($("#maincontainer").outerWidth() >= $(window).width()-10) {
		$("#maincontainer").addClass("width");
	}
}