function resetPassword(userID)
{
	$.ajax({
		url: "/users/user/resetpw",
        data: {
            id: userID,
            ajax: 1
        },
		success: function(data) {
			showPopup(data, 500, 250);
		}
	});
}
function banUser(userID)
{
	$.ajax({
		url: "/users/user/ban",
        data: {
            id: userID,
            ajax: 1
        },
		success: function(data) {
			showPopup(data, 500, 250);
		}
	});
}
function authorizeUser(userID)
{
	$.ajax({
		url: "/users/user/authorize",
        data: {
            id: userID,
            ajax: 1
        },
		success: function(data) {
			showPopup(data, 500, 250);
		}
	})
}

function showUserLogs()
{
	$("#userlogs").html("<img src='/images/loading.gif'> Loading logs");
	$.ajax({
		url: "/users/log/user",
		data: {
			user: $("#userid").val(),
            ajax: 1
		},
		success: function(data) {
			$("#userlogs").html(data);
		}
	});
}