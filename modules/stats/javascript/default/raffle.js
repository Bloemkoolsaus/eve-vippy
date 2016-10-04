function RollNumber(number)
{
	$("#rollresult"+number).html("<img src='/images/loading.gif'>");
	$.ajax({
		url: "index.php?module=stats&section=raffle",
		data: {
			ajax: 1,
			date: $("#ticketsdate").val(),
			ticket: $("#roll"+number).val()
		},
		success: function(data) {
			$("#rollresult"+number).html(data);
		}
	});
}