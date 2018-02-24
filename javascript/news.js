function showUnreadNews()
{
	$.ajax({
		url: "/vippy/news/unread",
		data: { ajax: 1 },
		success: function (data) {
            addDiv("disabledPage");
		    showPopup(data, 700, 400, null, null, function() {
		        $("#popupHeader").remove();
		        $("#popupFooter").remove();
		        $("#popup").height($("#popupContent").height()+50);
		        $("#popup").css("background", "url('/images/vippy/news.jpg') top right");
            });
        }
	});
}

function showNewsArticle(articleID)
{
	$.ajax({
		url: "/vippy/news/article/"+articleID,
		data: { ajax: 1 },
		success: function (data) {
            addDiv("disabledPage");
		    showPopup(data, 700, 400, null, null, function() {
		        $("#popupHeader").remove();
		        $("#popupFooter").remove();
		        $("#popup").height($("#popupContent").height()+50);
		        $("#popup").css("background", "url('/images/vippy/news.jpg') top right");
            });
        }
	});
}