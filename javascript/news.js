function showUnreadNews()
{
	$.ajax({
		url: "/vippy/news/unread",
		data: { ajax: 1 },
		success: function (data) {
		    showPopup(data, 600, 400);
        }
	});
}

function showNewsArticle(articleID)
{
	$.ajax({
		url: "/vippy/news/article/"+articleID,
		data: { ajax: 1 },
		success: function (data) {
		    showPopup(data, 600, 400);
        }
	});
}