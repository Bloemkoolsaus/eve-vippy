function manuallyAddMass()
{
	$("#editConnectionContainer").hide();
	$("#addMassContainer").show();
	addMassRecord();
}

function addMassRecord()
{
	$("#addMassRecords").append(Mustache.to_html($("#addShipMassTpl").html(), ""));	
}