function addNotice(notice)
{
	if ($("#shownotice"+notice.id).length > 0)
	{
		// Bestaat al..
	}
	else
	{
		var html = 	"<div id='shownotice"+notice.id+"' rel='notice' class='"+notice.type+"' style='display:none;'>";
		html += 	"<div><h2>"+notice.title+"</h2></div>";
		html += 	"<div>"+notice.body+"</div>";
		html += 	"</div>";

		$("#divNotices").append(html);

		$("#shownotice"+notice.id).fadeIn(false, function() {
			if ($("#shownotice"+notice.id+"close").length > 0)
			{
				var posTop = $("#shownotice"+notice.id).position().top + 10;
				var posLeft = $("#shownotice"+notice.id).position().left + $("#shownotice"+notice.id).width() - 70;
				$("#shownotice"+notice.id+"close").css("position","absolute");
				$("#shownotice"+notice.id+"close").css("top", posTop);
				$("#shownotice"+notice.id+"close").css("left", posLeft);
				$("#shownotice"+notice.id+"close").fadeIn(100);
			}
		});
		resizeMap();
	}
}

function createSystemNotice(systemID)
{
	loadingSigMap = true;
	loadingSigList = true;
	$.ajax({
		url: "/map/notice/add/"+systemID+"/"+$("#mapID").val(),
        data: { ajax: 1 },
		success: function(data) {
			showPopup(data, 650, 300);
		}
	});
}