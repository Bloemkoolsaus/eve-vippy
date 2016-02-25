function resetPassword(userID)
{
	$.ajax({
		url: "/index.php?module=users&action=resetpwform&ajax=1&id="+userID,
		success: function(data) {
			showPopup(data, 500, 250);
		}
	});
}
function banUser(userID)
{
	$.ajax({
		url: "/index.php?module=users&action=banform&ajax=1&id="+userID,
		success: function(data) {
			showPopup(data, 500, 250);
		}
	});
}
function authorizeUser(userID)
{
	$.ajax({
		url: "/index.php?module=users&action=authorizeform&ajax=1&id="+userID,
		success: function(data) {
			showPopup(data, 500, 250);
		}
	})
}
function validateAPI(userID)
{
	showPopup("<div style='margin: 20px; text-align: center;'><h3>Checking API</h3></div>", 300, 100);
	document.location = '/index.php?module=users&action=edit&id='+userID+'&validateapi=1';
}

function showUserEditTab(tab)
{
	$("[rel=detailtab]").hide();
	$("#detailtab-"+tab).fadeIn();
}

function showUserLogs()
{
	$("#userlogs").html("<img src='images/loading.gif'> Loading logs");
	$.ajax({
		url: "/index.php?module=users&action=edit&ajax=1",
		data: {
			action: "showlog",
			id:	$("#userid").val(),
		},
		success: function(data) {
			$("#userlogs").html(data);
		}
	});
}