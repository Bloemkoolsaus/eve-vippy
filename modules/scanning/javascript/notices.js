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
		
		if (notice.persistant < 1)
			html += "<div id='shownotice"+notice.id+"close' style='display:none;'><a href='#' onclick='hideNotice(\""+notice.id+"\")'>Hide Notice</a></div>";

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

function hideNotice(noticeID)
{
	$.ajax({
		url: "index.php?module=notices&section=fetch&action=markread&id="+noticeID+"&ajax=1",
		success: function(data ) { }
	})
	$("#shownotice"+noticeID).fadeOut(500, function() { $("#shownotice"+noticeID).remove(); });
}

function createSystemNotice(systemID)
{
	loadingSigMap = true;
	loadingSigList = true;
	$.ajax({
		url: "index.php?module=notices&section=map&action=new&ajax=1&systemid="+systemID+"&redirect=scanning",
		success: function(data) {
			showPopup("<div id='createNoticePopup' style='padding: 10px;'>"+data+"</div>", 650, 400);
		}
	});
}
function cancelCreateSystemNotice()
{
	loadingSigMap = false;
	loadingSigList = false;
	destroyPopup();
}