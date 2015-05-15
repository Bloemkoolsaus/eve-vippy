function preTextInput(id, text, blur)
{
	if (blur) {
		if ($("#"+id).attr("value").trim() == "") {
			$("#"+id).attr("value", text);
			$("#"+id).addClass("blurred");
		}
	} else {
		$("#"+id).attr("value", "");
		$("#"+id).removeClass("blurred");
	}
}