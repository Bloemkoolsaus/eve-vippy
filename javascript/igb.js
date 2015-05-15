
function isIGB()
{
	if (typeof CCPEVE === 'undefined')
		return false;
	else
		return true;
}

function trustIGB(trustableURL)
{
	try {
		CCPEVE.requestTrust(trustableURL);
	} catch(err) {
		alert("You are not in the ingame browser!");
	}
}

function igbShowInfo(itemID)
{
	if (typeof CCPEVE === 'undefined')
	{
		$.ajax({
			url: "index.php?module=eve&section=showinfo&ajax=1",
			data: {
				id: itemID
			},
			success: function(data) {
				showPopup(data, 500, 200, null, null, function() {
					setPopupHeight($("#showinfo").height() + 50);
				});
			}
		})		
	}
	else
		return CCPEVE.showInfo(itemID);
}

function setDestination(systemID)
{
	try {
		CCPEVE.setDestination(systemID);
	} catch(err) {
		alert("Cannot set destination!\nYou are not in the ingame browser.");
	}

	if ($("#wormholeContext").length > 0)
		$("#wormholeContext").remove();
}